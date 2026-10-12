<?php

namespace App\Services\Subsonic;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\SongRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Backs the Subsonic `search2`/`search3` endpoints. A blank query ("", '', or
 * absent) enumerates the whole library — the de facto Subsonic behavior clients
 * like Symfonium rely on to sync — while any other query runs a normal search.
 */
class SubsonicSearchService
{
    public function __construct(
        private readonly ArtistRepository $artistRepository,
        private readonly AlbumRepository $albumRepository,
        private readonly SongRepository $songRepository,
    ) {}

    /** @return Collection<int, Artist> */
    public function searchArtists(string $query, int $count, int $offset, User $user): Collection
    {
        return self::isBrowseAll($query)
            ? $this->artistRepository->getOrdered('artists.name', 'asc', $count, $offset, $user)
            : $this->artistRepository->search($query, $count, $user);
    }

    /** @return Collection<int, Album> */
    public function searchAlbums(string $query, int $count, int $offset, User $user): Collection
    {
        return self::isBrowseAll($query)
            ? $this->albumRepository->getOrdered('albums.name', 'asc', $count, $offset, $user)
            : $this->albumRepository->search($query, $count, $user);
    }

    /** @return Collection<int, Song> */
    public function searchSongs(string $query, int $count, int $offset, User $user): Collection
    {
        return (
            self::isBrowseAll($query)
                ? $this->songRepository->getOrdered(['songs.title'], 'asc', $count, $offset, $user)
                : $this->songRepository->search($query, $count, $user)
        );
    }

    private static function isBrowseAll(string $query): bool
    {
        $query = trim($query);

        return $query === '' || $query === '""' || $query === "''";
    }
}
