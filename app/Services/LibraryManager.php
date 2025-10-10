<?php

namespace App\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Services\Scanners\Contracts\ScannerCacheStrategy as CacheStrategy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

readonly class LibraryManager
{
    public function __construct(
        private CacheStrategy $cache,
    ) {
    }

    /**
     * Delete albums and artists that have no songs.
     *
     * @return array<mixed>
     */
    public function prune(bool $dryRun = false): array
    {
        $deletedAlbums = $this->pruneAlbums($dryRun);
        $deletedArtists = $this->pruneArtists($dryRun);

        if (!$dryRun) {
            $this->clearArtistCaches($deletedArtists);
            $this->clearAlbumCaches($deletedAlbums);
        }

        return ['albums' => $deletedAlbums, 'artists' => $deletedArtists];
    }

    private function clearAlbumCaches(Collection $albums): void
    {
        /** @var Album $album */
        foreach ($albums as $album) {
            $albumKey = Album::getCacheKey($album->artist_id, $album->name);
            $this->cache->forget($albumKey);
        }
    }

    private function clearArtistCaches(Collection $artists): void
    {
        /** @var Artist $artist */
        foreach ($artists as $artist) {
            $artistKey = Artist::getCacheKey($artist->user_id, $artist->name);
            $this->cache->forget($artistKey);
        }
    }

    /**
     * @return Collection<Album>
     */
    private function pruneAlbums(bool $dryRun): Collection
    {
        return DB::transaction(static function () use ($dryRun): Collection {
            $albumQuery = Album::query()
                ->leftJoin('songs', 'songs.album_id', '=', 'albums.id')
                ->whereNull('songs.album_id');

            $result = $albumQuery->get('albums.*');

            if (!$dryRun) {
                $albumQuery->delete();
            }

            return $result;
        });
    }

    /**
     * @return Collection<Artist>
     */
    private function pruneArtists(bool $dryRun): Collection
    {
        return DB::transaction(static function () use ($dryRun): Collection {
            $artistQuery = Artist::query()
                ->leftJoin('songs', 'songs.artist_id', '=', 'artists.id')
                ->leftJoin('albums', 'albums.artist_id', '=', 'artists.id')
                ->whereNull('songs.artist_id')
                ->whereNull('albums.artist_id');

            $result = $artistQuery->get('artists.*');

            if (!$dryRun) {
                $artistQuery->delete();
            }

            return $result;
        });
    }
}
