<?php

namespace App\Console\Commands;

use App\Models\Album;
use App\Models\Artist;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Services\Integrations\MbidService;
use App\Services\Integrations\MusicBrainzRateLimiter;
use App\Services\Integrations\MusicBrainzService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Throwable;

class FetchMbidsCommand extends Command
{
    protected $signature = 'koel:fetch-mbids';

    protected $description = 'Attempt to fetch missing MusicBrainz identifiers for albums, artists and songs.';

    public function __construct(
        private readonly MbidService $mbidService,
        private readonly AlbumRepository $albumRepository,
        private readonly ArtistRepository $artistRepository,
        private readonly MusicBrainzRateLimiter $rateLimiter,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!MusicBrainzService::enabled()) {
            $this->error('MusicBrainz is disabled. Enable it before running this command.');

            return self::FAILURE;
        }

        $albumCount = $this->albumRepository->countWithIncompleteMbids();
        $artistCount = $this->artistRepository->countWithoutMbid();

        if ($albumCount === 0 && $artistCount === 0) {
            $this->info('Every album, artist and song already has an identifier.');

            return self::SUCCESS;
        }

        $this->rateLimiter->waitForRequestSlotsUpTo(INF, function () use ($albumCount, $artistCount): void {
            $this->lookUp($this->albumRepository->lazyGetWithIncompleteMbids(), $albumCount, 'album');
            $this->lookUp($this->artistRepository->lazyGetWithoutMbid(), $artistCount, 'artist');
        });

        $this->newLine();
        $this->info('Done. Run the command again to continue where an interrupted run left off.');

        return self::SUCCESS;
    }

    /**
     * @template TEntity of Album|Artist
     *
     * @param LazyCollection<array-key, TEntity> $entities
     */
    private function lookUp(LazyCollection $entities, int $total, string $noun): void
    {
        $this->info(sprintf('Looking up %s.', Str::plural($noun, $total, prependCount: true)));

        $progress = $this->output->createProgressBar($total);
        $progress->setFormat(' %current%/%max% [%bar%] %message%');

        foreach ($entities as $entity) {
            $progress->setMessage(self::getLabelForEntity($entity));
            $progress->display();

            $this->fetchIdentifiersFor($entity);
            $progress->advance();
        }

        $progress->setMessage('');
        $progress->display();
        $progress->finish();
        $this->newLine();
    }

    private function fetchIdentifiersFor(Album|Artist $entity): void
    {
        try {
            if ($entity instanceof Album) {
                $this->mbidService->fetchAndStoreAlbumMbids($entity);
            } else {
                $this->mbidService->fetchAndStoreArtistMbid($entity);
            }
        } catch (Throwable $e) {
            Log::error($e);
            $this->newLine();
            $this->warn(sprintf('Could not look up "%s": %s', self::getLabelForEntity($entity), $e->getMessage()));
        }
    }

    private static function getLabelForEntity(Album|Artist $entity): string
    {
        return $entity instanceof Album ? sprintf('%s - %s', $entity->name, $entity->artist_name) : $entity->name;
    }
}
