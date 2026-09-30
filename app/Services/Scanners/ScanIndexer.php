<?php

namespace App\Services\Scanners;

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

    public function __construct(
        private readonly SongRepository $songRepository,
        #[Config('scout.driver')]
        private readonly ?string $searchDriver,
    ) {}

    public function reindex(ScanResultCollection $results): void
    {
        if ($this->searchDriver !== 'tntsearch') {
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
        $artists->searchable(); // @phpstan-ignore-line
        $genres->searchable(); // @phpstan-ignore-line
    }
}
