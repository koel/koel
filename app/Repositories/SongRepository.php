<?php

namespace App\Repositories;

use App\Builders\SongBuilder;
use App\Enums\PlayableType;
use App\Facades\License;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Folder;
use App\Models\Playlist;
use App\Models\Podcast;
use App\Models\Setting;
use App\Models\Song;
use App\Models\User;
use App\Values\Genre;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/** @extends Repository<Song> */
class SongRepository extends Repository
{
    private const DEFAULT_QUEUE_LIMIT = 500;

    public function findOneByPath(string $path): ?Song
    {
        return Song::query()->where('path', $path)->first();
    }

    /** @return Collection|array<array-key, Song> */
    public function getAllStoredOnCloud(): Collection
    {
        return Song::query()->storedOnCloud()->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getRecentlyAdded(int $count = 10, ?User $scopedUser = null): Collection
    {
        return Song::query(type: PlayableType::SONG, user: $scopedUser ?? $this->auth->user())
            ->accessible()
            ->withMeta()
            ->latest()
            ->limit($count)
            ->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getMostPlayed(int $count = 7, ?User $scopedUser = null): Collection
    {
        return Song::query(user: $scopedUser ?? $this->auth->user())
            ->accessible()
            ->withMeta(requiresInteractions: true)
            ->where('interactions.play_count', '>', 0)
            ->orderByDesc('interactions.play_count')
            ->limit($count)
            ->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getRecentlyPlayed(int $count = 7, ?User $scopedUser = null): Collection
    {
        return Song::query(user: $scopedUser ?? $this->auth->user())
            ->accessible()
            ->withMeta(requiresInteractions: true)
            ->where('interactions.play_count', '>', 0)
            ->addSelect('interactions.last_played_at')
            ->orderByDesc('interactions.last_played_at')
            ->limit($count)
            ->get();
    }

    public function getForListing(
        array $sortColumns,
        string $sortDirection,
        bool $ownSongsOnly = false,
        ?User $scopedUser = null,
        int $perPage = 50
    ): Paginator {
        $scopedUser ??= $this->auth->user();

        return Song::query(type: PlayableType::SONG, user: $scopedUser)
            ->accessible()
            ->withMeta()
            ->when(
                $ownSongsOnly,
                static fn (SongBuilder $query) => $query->where('songs.owner_id', $scopedUser->id)
            )
            ->sort($sortColumns, $sortDirection)
            ->simplePaginate($perPage);
    }

    public function getByGenre(
        string $genre,
        array $sortColumns,
        string $sortDirection,
        ?User $scopedUser = null,
        int $perPage = 50
    ): Paginator {
        return Song::query(type: PlayableType::SONG, user: $scopedUser ?? $this->auth->user())
            ->accessible()
            ->withMeta()
            ->where('genre', $genre)
            ->sort($sortColumns, $sortDirection)
            ->simplePaginate($perPage);
    }

    /** @return Collection|array<array-key, Song> */
    public function getForQueue(
        array $sortColumns,
        string $sortDirection,
        int $limit = self::DEFAULT_QUEUE_LIMIT,
        ?User $scopedUser = null,
    ): Collection {
        return Song::query(type: PlayableType::SONG, user: $scopedUser ?? $this->auth->user())
            ->accessible()
            ->withMeta()
            ->sort($sortColumns, $sortDirection)
            ->limit($limit)
            ->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getFavorites(?User $scopedUser = null): Collection
    {
        return Song::query(user: $scopedUser ?? $this->auth->user())
            ->accessible()
            ->withMeta()
            ->where('interactions.liked', true)
            ->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getByAlbum(Album $album, ?User $scopedUser = null): Collection
    {
        return Song::query(user: $scopedUser ?? $this->auth->user())
            ->accessible()
            ->withMeta()
            ->whereBelongsTo($album)
            ->orderBy('songs.disc')
            ->orderBy('songs.track')
            ->orderBy('songs.title')
            ->get();
    }

    public function paginateInFolder(?Folder $folder = null, ?User $scopedUser = null): Paginator
    {
        return Song::query(type: PlayableType::SONG, user: $scopedUser ?? $this->auth->user())
            ->accessible()
            ->withMeta()
            ->when($folder, static fn (SongBuilder $query) => $query->where('folder_id', $folder->id)) // @phpstan-ignore-line
            ->when(!$folder, static fn (SongBuilder $query) => $query->whereNull('folder_id'))
            ->orderBy('songs.path')
            ->simplePaginate(50);
    }

    /** @return Collection|array<array-key, Song> */
    public function getByArtist(Artist $artist, ?User $scopedUser = null): Collection
    {
        return Song::query(type: PlayableType::SONG, user: $scopedUser ?? $this->auth->user())
            ->accessible()
            ->withMeta()
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
        return Song::query(user: $scopedUser ?? $this->auth->user())
            ->accessible()
            ->withMeta()
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
        return Song::query(type: PlayableType::SONG, user: $scopedUser ?? $this->auth->user())
            ->accessible()
            ->withMeta()
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getMany(array $ids, bool $preserveOrder = false, ?User $scopedUser = null): Collection
    {
        $songs = Song::query(user: $scopedUser ?? $this->auth->user())
            ->accessible()
            ->withMeta()
            ->whereIn('songs.id', $ids)
            ->get();

        return $preserveOrder ? $songs->orderByArray($ids) : $songs; // @phpstan-ignore-line
    }

    /**
     * Gets several songs, but also includes collaborative information.
     *
     * @return Collection|array<array-key, Song>
     */
    public function getManyInCollaborativeContext(array $ids, ?User $scopedUser = null): Collection
    {
        return Song::query(user: $scopedUser ?? $this->auth->user())
            ->accessible()
            ->withMeta()
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
        return Song::query(user: $scopedUser ?? $this->auth->user())
            ->accessible()
            ->withMeta()
            ->findOrFail($id);
    }

    /** @param string $id */
    public function findOne($id, ?User $scopedUser = null): ?Song
    {
        return Song::query(user: $scopedUser ?? $this->auth->user())
            ->accessible()
            ->withMeta()
            ->find($id);
    }

    public function countSongs(?User $scopedUser = null): int
    {
        return Song::query(type: PlayableType::SONG, user: $scopedUser ?? $this->auth->user())
            ->accessible()
            ->count();
    }

    public function getTotalSongLength(?User $scopedUser = null): float
    {
        return Song::query(type: PlayableType::SONG, user: $scopedUser ?? $this->auth->user())
            ->accessible()
            ->sum('length');
    }

    /** @return Collection|array<array-key, Song> */
    public function getRandomByGenre(string $genre, int $limit, ?User $scopedUser = null): Collection
    {
        return Song::query(type: PlayableType::SONG, user: $scopedUser ?? $this->auth->user())
            ->accessible()
            ->withMeta()
            ->where('genre', $genre === Genre::NO_GENRE ? '' : $genre)
            ->limit($limit)
            ->inRandomOrder()
            ->get();
    }

    /** @return array<string> */
    public function getEpisodeGuidsByPodcast(Podcast $podcast): array
    {
        return $podcast->episodes()->pluck('episode_guid')->toArray();
    }

    /** @return Collection<Song> */
    public function getEpisodesByPodcast(Podcast $podcast): Collection
    {
        return $podcast->episodes;
    }

    /** @return Collection<Song> */
    public function getUnderPaths(
        array $paths,
        int $limit = 500,
        bool $random = false,
        ?User $scopedUser = null
    ): Collection {
        $paths = self::normalizePaths($paths);

        if (!$paths) {
            return collect();
        }

        return Song::query(type: PlayableType::SONG, user: $scopedUser ?? $this->auth->user())
            ->accessible()
            ->withMeta()
            ->where(static function (SongBuilder $query) use ($paths): void {
                foreach ($paths as $path) {
                    $query->orWhere('songs.path', 'LIKE', $path . '%');
                }
            })
            ->when(!$random, static fn (SongBuilder $query): SongBuilder => $query->orderBy('songs.path'))
            ->when($random, static fn (SongBuilder $query): SongBuilder => $query->inRandomOrder())
            ->limit($limit)
            ->get();
    }

    /** @return array<string> */
    private static function normalizePaths(array $paths): array
    {
        $root = Setting::get('media_path');
        $root = DIRECTORY_SEPARATOR . trim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR; # /var/www/media/

        $normalizePath = static function (string $path) use ($root): string {
            if (!$path) {
                return $root;
            }

            $path = trim($path, DIRECTORY_SEPARATOR);

            return $root . $path . DIRECTORY_SEPARATOR; # /var/www/media/foo/bar/
        };

        $removeSubpaths = static function (array $paths): array {
            $result = [];

            foreach ($paths as $path) {
                $isSubpath = false;

                foreach ($result as $parent) {
                    if (Str::startsWith($path, $parent)) {
                        $isSubpath = true;

                        break;
                    }
                }

                if (!$isSubpath) {
                    $result[] = $path;
                }
            }

            return $result;
        };

        return $removeSubpaths(array_map($normalizePath, $paths));
    }
}
