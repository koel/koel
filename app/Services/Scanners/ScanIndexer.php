<?php

namespace App\Services\Scanners;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Song;
use App\Values\Scanning\ScanResult;
use App\Values\Scanning\ScanResultCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScanIndexer
{
    private const int CHUNK_SIZE = 500;

    public function reindex(ScanResultCollection $results): void
    {
        if (config('scout.driver') !== 'tntsearch') {
            return;
        }

        $results
            ->success()
            ->map(static fn (ScanResult $result): string => $result->path)
            ->chunk(self::CHUNK_SIZE)
            ->each(static function (Collection $paths): void {
                try {
                    self::index($paths);
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

    private static function index(Collection $paths): void
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Song> $songs */
        $songs = Song::query()->whereIn('path', $paths->all())->get();

        if ($songs->isEmpty()) {
            return;
        }

        $songs->searchable(); // @phpstan-ignore-line
        Album::query()->whereKey($songs->pluck('album_id')->unique()->filter())->get()->searchable(); // @phpstan-ignore-line

        $artistIds = $songs->pluck('artist_id')->merge($songs->pluck('album.artist_id'))->unique()->filter();
        Artist::query()->whereKey($artistIds)->get()->searchable(); // @phpstan-ignore-line
        Genre::query()->whereKey($songs->pluck('genres.*.id')->flatten()->unique())->get()->searchable(); // @phpstan-ignore-line
    }
}
