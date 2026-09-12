<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ThrottlesMusicBrainzRequests;
use App\Models\Album;
use App\Models\Artist;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Services\Integrations\MbidService;
use App\Services\Integrations\MusicBrainzService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class FetchMbidsCommand extends Command
{
    use ThrottlesMusicBrainzRequests;

    protected $signature = 'koel:fetch-mbids';

    protected $description = 'Attempt to fetch missing MusicBrainz identifiers for albums and artists.';

    public function __construct(
        private readonly MbidService $mbidService,
        private readonly AlbumRepository $albumRepository,
        private readonly ArtistRepository $artistRepository,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!MusicBrainzService::enabled()) {
            $this->error('MusicBrainz is disabled. Enable it before running this command.');

            return self::FAILURE;
        }

        $this->throttleMusicBrainzRequests();

        $albums = $this->albumRepository->getWithoutMbid();
        $artists = $this->artistRepository->getWithoutMbid();

        if ($albums->isEmpty() && $artists->isEmpty()) {
            $this->info('Every album and artist already has an identifier.');

            return self::SUCCESS;
        }

        $this->lookUp($albums, 'album', $this->mbidService->fetchAndStoreAlbumMbids(...));
        $this->lookUp($artists, 'artist', $this->mbidService->fetchAndStoreArtistMbid(...));

        $this->newLine();
        $this->info('Done. Run the command again to continue where an interrupted run left off.');

        return self::SUCCESS;
    }

    /**
     * @param Collection<array-key, Album|Artist> $entities
     * @param callable(Album|Artist): void $lookUp
     */
    private function lookUp(Collection $entities, string $label, callable $lookUp): void
    {
        $this->info(sprintf('Looking up %s.', Str::plural($label, $entities, prependCount: true)));

        $progress = $this->output->createProgressBar($entities->count());

        foreach ($entities as $entity) {
            $this->rescueLookup(static fn () => $lookUp($entity), $entity->name);
            $progress->advance();
        }

        $progress->finish();
        $this->newLine();
    }

    private function rescueLookup(callable $lookup, string $name): void
    {
        try {
            $lookup();
        } catch (Throwable $e) {
            Log::error($e);
            $this->newLine();
            $this->warn(sprintf('Could not look up "%s": %s', $name, $e->getMessage()));
        }
    }
}
