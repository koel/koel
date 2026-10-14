<?php

namespace App\Services\Scanners;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Song;
use App\Repositories\SongRepository;
use App\Values\Scanning\ScanResult;
use App\Values\Scanning\ScanResultCollection;
use Illuminate\Container\Attributes\Config;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScanIndexer
{
    private const int CHUNK_SIZE = 500;

    private const array SCANNED_SEARCHABLE_MODELS = [Song::class, Album::class, Artist::class, Genre::class];

    private const array DRIVERS_WITHOUT_AN_INDEX = [null, 'null', 'database', 'collection'];

    public function __construct(
        private readonly SongRepository $songRepository,
        #[Config('scout.driver')]
        private readonly ?string $searchDriver,
    ) {}

    public function withoutSyncingToSearch(callable $scan): ScanResultCollection
    {
        foreach (self::SCANNED_SEARCHABLE_MODELS as $model) {
            $model::disableSearchSyncing();
        }

        try {
            return $scan();
        } finally {
            foreach (self::SCANNED_SEARCHABLE_MODELS as $model) {
                $model::enableSearchSyncing();
            }
        }
    }

    public function reindex(ScanResultCollection $results): void
    {
        if (in_array($this->searchDriver, self::DRIVERS_WITHOUT_AN_INDEX, true)) {
            return;
        }

        $results
            ->reject(static fn (ScanResult $result): bool => $result->isSkipped())
            ->map(static fn (ScanResult $result): string => $result->path)
            ->chunk(self::CHUNK_SIZE)
            ->each(function (Collection $paths): void {
                try {
                    $this->index($paths);
                } catch (Throwable $e) {
                    Log::warning(sprintf(
                        'Could not index %d scanned song(s) starting at %s: %s',
                        $paths->count(),
                        $paths->first(),
                        $e->getMessage(),
                    ));
                }
            });
    }

    private function index(Collection $paths): void
    {
        $songs = $this->songRepository->getManyByPaths($paths->all());

        if ($songs->isEmpty()) {
            return;
        }

        $albums = EloquentCollection::make($songs->pluck('album')->filter()->unique('id'));
        $artists = EloquentCollection::make(
            $songs->pluck('artist')->merge($albums->pluck('artist'))->filter()->unique('id'),
        );
        $genres = EloquentCollection::make($songs->pluck('genres')->collapse()->unique('id'));

        $songs->searchable(); // @phpstan-ignore-line
        $albums->searchable(); // @phpstan-ignore-line
        $artists->filter->shouldBeSearchable()->searchable(); // @phpstan-ignore-line
        $genres->searchable(); // @phpstan-ignore-line
    }
}
