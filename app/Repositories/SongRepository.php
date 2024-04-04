<?php

namespace App\Repositories;

use App\Builders\SongBuilder;
use App\Facades\License;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Song;
use App\Models\User;
use App\Values\Genre;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;

class SongRepository extends Repository
{
    private const DEFAULT_QUEUE_LIMIT = 500;

    public function findOneByPath(string $path): ?Song
    {
        return Song::query()->where('path', $path)->first();
    }

    /** @return Collection|array<Song> */
    public function getAllStoredOnCloud(): Collection
    {
        return Song::query()->storedOnCloud()->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getRecentlyAdded(int $count = 10, ?User $scopedUser = null): Collection
    {
        /** @var ?User $scopedUser */
        $scopedUser ??= $this->auth->user();

        return Song::query()
            ->accessibleBy($scopedUser)
            ->withMetaFor($scopedUser)
            ->latest()
            ->limit($count)
            ->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getMostPlayed(int $count = 7, ?User $scopedUser = null): Collection
    {
        /** @var ?User $scopedUser */
        $scopedUser ??= $this->auth->user();

        return Song::query()
            ->accessibleBy($scopedUser)
            ->withMetaFor($scopedUser, requiresInteractions: true)
            ->where('interactions.play_count', '>', 0)
            ->orderByDesc('interactions.play_count')
            ->limit($count)
            ->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getRecentlyPlayed(int $count = 7, ?User $scopedUser = null): Collection
    {
        /** @var ?User $scopedUser */
        $scopedUser ??= $this->auth->user();

        return Song::query()
            ->accessibleBy($scopedUser)
            ->withMetaFor($scopedUser, requiresInteractions: true)
            ->orderByDesc('interactions.last_played_at')
            ->limit($count)
            ->get();
    }

    public function getForListing(
        string $sortColumn,
        string $sortDirection,
        bool $ownSongsOnly = false,
        ?User $scopedUser = null,
        int $perPage = 50
    ): Paginator {
        /** @var ?User $scopedUser */
        $scopedUser ??= $this->auth->user();

        return Song::query()
            ->accessibleBy($scopedUser)
            ->withMetaFor($scopedUser)
            ->when($ownSongsOnly, static fn (SongBuilder $query) => $query->where('songs.owner_id', $scopedUser->id))
            ->sort($sortColumn, $sortDirection)
            ->simplePaginate($perPage);
    }

    public function getByGenre(
        string $genre,
        string $sortColumn,
        string $sortDirection,
        ?User $scopedUser = null,
        int $perPage = 50
    ): Paginator {
        /** @var ?User $scopedUser */
        $scopedUser ??= $this->auth->user();

        return Song::query()
            ->accessibleBy($scopedUser)
            ->withMetaFor($scopedUser)
            ->where('genre', $genre)
            ->sort($sortColumn, $sortDirection)
            ->simplePaginate($perPage);
    }

    /** @return Collection|array<array-key, Song> */
    public function getForQueue(
        string $sortColumn,
        string $sortDirection,
        int $limit = self::DEFAULT_QUEUE_LIMIT,
        ?User $scopedUser = null,
    ): Collection {
        /** @var ?User $scopedUser */
        $scopedUser ??= $this->auth->user();

        return Song::query()
            ->accessibleBy($scopedUser)
            ->withMetaFor($scopedUser)
            ->sort($sortColumn, $sortDirection)
            ->limit($limit)
            ->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getFavorites(?User $scopedUser = null): Collection
    {
        /** @var ?User $scopedUser */
        $scopedUser ??= $this->auth->user();

        return Song::query()
            ->accessibleBy($scopedUser)
            ->withMetaFor($scopedUser)
            ->where('interactions.liked', true)
            ->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getByAlbum(Album $album, ?User $scopedUser = null): Collection
    {
        /** @var ?User $scopedUser */
        $scopedUser ??= $this->auth->user();

        return Song::query()
            ->accessibleBy($scopedUser)
            ->withMetaFor($scopedUser)
            ->where('album_id', $album->id)
            ->orderBy('songs.disc')
            ->orderBy('songs.track')
            ->orderBy('songs.title')
            ->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getByArtist(Artist $artist, ?User $scopedUser = null): Collection
    {
        /** @var ?User $scopedUser */
        $scopedUser ??= $this->auth->user();

        return Song::query()
            ->accessibleBy($scopedUser)
            ->withMetaFor($scopedUser)
            ->where('songs.artist_id', $artist->id)
            ->orWhere('albums.artist_id', $artist->id)
            ->orderBy('albums.name')
            ->orderBy('songs.disc')
            ->orderBy('songs.track')
            ->orderBy('songs.title')
            ->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getByStandardPlaylist(Playlist $playlist, ?User $scopedUser = null): Collection
    {
        /** @var ?User $scopedUser */
        $scopedUser ??= $this->auth->user();

        return Song::query()
            ->accessibleBy($scopedUser)
            ->withMetaFor($scopedUser)
            ->leftJoin('playlist_song', 'songs.id', '=', 'playlist_song.song_id')
            ->leftJoin('playlists', 'playlists.id', '=', 'playlist_song.playlist_id')
            ->when(License::isPlus(), static function (SongBuilder $query): SongBuilder {
                return
                    $query->join('users as collaborators', 'playlist_song.user_id', '=', 'collaborators.id')
                        ->addSelect(
                            'collaborators.id as collaborator_id',
                            'collaborators.name as collaborator_name',
                            'collaborators.email as collaborator_email',
                            'collaborators.avatar as collaborator_avatar',
                            'playlist_song.created_at as added_at'
                        );
            })
            ->where('playlists.id', $playlist->id)
            ->orderBy('playlist_song.position')
            ->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getRandom(int $limit, ?User $scopedUser = null): Collection
    {
        /** @var ?User $scopedUser */
        $scopedUser ??= $this->auth->user();

        return Song::query()
            ->accessibleBy($scopedUser)
            ->withMetaFor($scopedUser)
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getMany(array $ids, bool $inThatOrder = false, ?User $scopedUser = null): Collection
    {
        /** @var ?User $scopedUser */
        $scopedUser ??= $this->auth->user();

        $songs = Song::query()
            ->accessibleBy($scopedUser)
            ->withMetaFor($scopedUser)
            ->whereIn('songs.id', $ids)
            ->get();

        return $inThatOrder ? $songs->orderByArray($ids) : $songs;
    }

    /**
     * Gets several songs, but also includes collaborative information.
     *
     * @return Collection|array<array-key, Song>
     */
    public function getManyInCollaborativeContext(array $ids, ?User $scopedUser = null): Collection
    {
        /** @var ?User $scopedUser */
        $scopedUser ??= $this->auth->user();

        return Song::query()
            ->accessibleBy($scopedUser)
            ->withMetaFor($scopedUser)
            ->when(License::isPlus(), static function (SongBuilder $query): SongBuilder {
                return
                    $query->leftJoin('playlist_song', 'songs.id', '=', 'playlist_song.song_id')
                        ->leftJoin('playlists', 'playlists.id', '=', 'playlist_song.playlist_id')
                        ->join('users as collaborators', 'playlist_song.user_id', '=', 'collaborators.id')
                        ->addSelect(
                            'collaborators.id as collaborator_id',
                            'collaborators.name as collaborator_name',
                            'collaborators.email as collaborator_email',
                            'playlist_song.created_at as added_at'
                        );
            })
            ->whereIn('songs.id', $ids)
            ->get();
    }

    /** @param string $id */
    public function getOne($id, ?User $scopedUser = null): Song
    {
        /** @var ?User $scopedUser */
        $scopedUser ??= $this->auth->user();

        return Song::query()
            ->accessibleBy($scopedUser)
            ->withMetaFor($scopedUser)
            ->findOrFail($id);
    }

    /** @param string $id */
    public function findOne($id, ?User $scopedUser = null): ?Song
    {
        /** @var ?User $scopedUser */
        $scopedUser ??= $this->auth->user();

        return Song::query()
            ->accessibleBy($scopedUser)
            ->withMetaFor($scopedUser ?? $this->auth->user())
            ->find($id);
    }

    public function count(?User $scopedUser = null): int
    {
        return Song::query()->accessibleBy($scopedUser ?? auth()->user())->count();
    }

    public function getTotalLength(?User $scopedUser = null): float
    {
        return Song::query()->accessibleBy($scopedUser ?? auth()->user())->sum('length');
    }

    /** @return Collection|array<array-key, Song> */
    public function getRandomByGenre(string $genre, int $limit, ?User $scopedUser = null): Collection
    {
        /** @var ?User $scopedUser */
        $scopedUser ??= $this->auth->user();

        return Song::query()
            ->accessibleBy($scopedUser)
            ->withMetaFor($scopedUser)
            ->where('genre', $genre === Genre::NO_GENRE ? '' : $genre)
            ->limit($limit)
            ->inRandomOrder()
            ->get();
    }
}
