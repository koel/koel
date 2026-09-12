<?php

namespace App\Console\Commands;

use App\Http\Integrations\MusicBrainz\MusicBrainzConnector;
use App\Http\Integrations\MusicBrainz\ThrottledMusicBrainzConnector;
use App\Models\Album;
use App\Models\Artist;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Services\Integrations\MbidService;
use App\Services\Integrations\MusicBrainzService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class BackfillMusicBrainzIdentifiersCommand extends Command
{
    /**
     * MusicBrainz asks for at most one request per second, and an album costs several of them.
     * @link https://musicbrainz.org/doc/MusicBrainz_API/Rate_Limiting
     */
    private const float REQUEST_INTERVAL_IN_SECONDS = 1.0;

    protected $signature = 'koel:musicbrainz:backfill
        {--limit= : Stop after this many albums and this many artists}';

    protected $description = 'Fill in missing MusicBrainz identifiers for albums and artists.';

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

        $limit = $this->option('limit') === null ? null : (int) $this->option('limit');

        $albums = $this->albumRepository->getWithoutMbid($limit);
        $artists = $this->artistRepository->getWithoutMbid($limit);

        if ($albums->isEmpty() && $artists->isEmpty()) {
            $this->info('Every album and artist already has an identifier.');

            return self::SUCCESS;
        }

        $this->backfillAlbums($albums);
        $this->backfillArtists($artists);

        $this->newLine();
        $this->info('Done. Run the command again to continue where an interrupted run left off.');

        return self::SUCCESS;
    }

    /**
     * Every lookup is cached indefinitely and no identifier is ever overwritten, so an interrupted run
     * costs nothing to repeat: the next one sees only what is still missing.
     */
    private function throttleMusicBrainzRequests(): void
    {
        $this->laravel->instance(
            MusicBrainzConnector::class,
            new ThrottledMusicBrainzConnector(self::REQUEST_INTERVAL_IN_SECONDS),
        );
    }

    /** @param \Illuminate\Database\Eloquent\Collection<array-key, Album> $albums */
    private function backfillAlbums(iterable $albums): void
    {
        $this->info(sprintf('Looking up %d album(s).', count($albums)));

        $progress = $this->output->createProgressBar(count($albums));

        foreach ($albums as $album) {
            $this->rescueLookup(fn () => $this->mbidService->fetchAndStoreAlbumMbids($album), $album->name);
            $progress->advance();
        }

        $progress->finish();
        $this->newLine();
    }

    /** @param \Illuminate\Database\Eloquent\Collection<array-key, Artist> $artists */
    private function backfillArtists(iterable $artists): void
    {
        $this->info(sprintf('Looking up %d artist(s).', count($artists)));

        $progress = $this->output->createProgressBar(count($artists));

        foreach ($artists as $artist) {
            $this->rescueLookup(fn () => $this->mbidService->fetchAndStoreArtistMbid($artist), $artist->name);
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
