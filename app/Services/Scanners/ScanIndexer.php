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

/**
 * Puts the songs a parallel scan saved, and their albums, artists and genres, into the search
 * index from ONE process.
 *
 * The scan workers run with search syncing off (see ScanChunkCommand): the default Scout
 * driver is TNTSearch, whose index is a SQLite file with one writer, and four workers saving
 * Searchable models at once collided on it with "database is locked", which then counted a
 * perfectly good file as invalid. Indexing here, after the workers have returned, keeps the
 * writes serial without giving up the parallel tagging.
 */
class ScanIndexer
{
    private const int CHUNK_SIZE = 500;

    public function reindex(ScanResultCollection $results): void
    {
        $results
            ->success()
            ->map(static fn (ScanResult $result): string => $result->path)
            ->chunk(self::CHUNK_SIZE)
            ->each(static function (Collection $paths): void {
                // One chunk failing must not abort the scan: the workers have already committed
                // every song, MediaScanCompleted still has to fire for the deletion pass, and a
                // rescan would classify these songs as unchanged and never index them. Say
                // where it stopped instead; `scout:import` over the model is the recovery.
                try {
                    self::index($paths);
                } catch (Throwable $e) {
                    Log::warning(sprintf(
                        'Indexing the chunk of %d scanned song(s) starting at %s stopped with "%s". '
                        . 'Whatever that step had not reached (songs, then albums, artists, genres) '
                        . 'is in the library but will not turn up in search until re-indexed.',
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

        // Both the track artist and the album artist: a compilation's album artist is not a
        // track artist, and the worker created or updated it too.
        $artistIds = $songs->pluck('artist_id')->merge($songs->pluck('album.artist_id'))->unique()->filter();
        Artist::query()->whereKey($artistIds)->get()->searchable(); // @phpstan-ignore-line
        Genre::query()->whereKey($songs->pluck('genres.*.id')->flatten()->unique())->get()->searchable(); // @phpstan-ignore-line
    }
}
