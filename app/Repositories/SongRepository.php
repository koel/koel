<?php

namespace App\Repositories;

use App\Builders\SongBuilder;
use App\Enums\EmbeddableType;
use App\Enums\PlayableType;
use App\Exceptions\EmbeddableNotFoundException;
use App\Exceptions\NonSmartPlaylistException;
use App\Facades\License;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Embed;
use App\Models\Folder;
use App\Models\Genre;
use App\Models\Playlist;
use App\Models\Podcast;
use App\Models\Song;
use App\Models\User;
use App\Repositories\Contracts\ScoutableRepository;
use App\Values\SmartPlaylist\SmartPlaylistQueryModifier as QueryModifier;
use App\Values\SmartPlaylist\SmartPlaylistRule as Rule;
use App\Values\SmartPlaylist\SmartPlaylistRuleGroup as RuleGroup;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use LogicException;

/**
 * @extends Repository<Song>
 * @implements ScoutableRepository<Song>
 */
class SongRepository extends Repository implements ScoutableRepository
{
    private const LIST_SIZE_LIMIT = 500;

    public function __construct(private readonly FolderRepository $folderRepository)
    {
        parent::__construct();
    }

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
    public function getRecentlyAdded(int $count = 8, ?User $scopedUser = null): Collection
    {
        return Song::query(type: PlayableType::SONG, user: $scopedUser ?? $this->auth->user())
            ->withUserContext()
            ->latest()
            ->limit($count)
            ->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getMostPlayed(int $count = 8, ?User $scopedUser = null): Collection
    {
        return Song::query(user: $scopedUser ?? $this->auth->user())
            ->withUserContext()
            ->where('interactions.play_count', '>', 0)
            ->orderByDesc('interactions.play_count')
            ->limit($count)
            ->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getRecentlyPlayed(int $count = 8, ?User $scopedUser = null): Collection
    {
        return Song::query(user: $scopedUser ?? $this->auth->user())
            ->withUserContext()
            ->where('interactions.play_count', '>', 0)
            ->addSelect('interactions.last_played_at')
            ->orderByDesc('interactions.last_played_at')
            ->limit($count)
            ->get();
    }

    public function paginate(
        array $sortColumns,
        string $sortDirection,
        ?User $scopedUser = null,
        int $perPage = 50
    ): Paginator {
        $scopedUser ??= $this->auth->user();

        return Song::query(type: PlayableType::SONG, user: $scopedUser)
            ->withUserContext()
            ->sort($sortColumns, $sortDirection)
            ->simplePaginate($perPage);
    }

    /**
     * @param Genre|null $genre If null, paginate songs that have no genre
     */
    public function paginateByGenre(
        ?Genre $genre,
        array $sortColumns,
        string $sortDirection,
        ?User $scopedUser = null,
        int $perPage = 50
    ): Paginator {
        return Song::query(type: PlayableType::SONG, user: $scopedUser ?? $this->auth->user())
            ->withUserContext()
            ->when($genre, static fn (Builder $builder) => $builder->whereRelation('genres', 'genres.id', $genre->id))
            ->when(!$genre, static fn (Builder $builder) => $builder->whereDoesntHave('genres'))
            ->sort($sortColumns, $sortDirection)
            ->simplePaginate($perPage);
    }

    /** @return Collection|array<array-key, Song> */
    public function getForQueue(
        array $sortColumns,
        string $sortDirection,
        int $limit = self::LIST_SIZE_LIMIT,
        ?User $scopedUser = null,
    ): Collection {
        return Song::query(type: PlayableType::SONG, user: $scopedUser ?? $this->auth->user())
            ->withUserContext()
            ->sort($sortColumns, $sortDirection)
            ->limit($limit)
            ->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getFavorites(?User $scopedUser = null): Collection
    {
        return Song::query(user: $scopedUser ?? $this->auth->user())
            ->withUserContext(favoritesOnly: true)
            ->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getByAlbum(Album $album, ?User $scopedUser = null): Collection
    {
        return Song::query(user: $scopedUser ?? $this->auth->user())
            ->withUserContext()
            ->whereBelongsTo($album)
            ->orderBy('songs.disc')
            ->orderBy('songs.track')
            ->orderBy('songs.title')
            ->get();
    }

    public function paginateInFolder(?Folder $folder = null, ?User $scopedUser = null): Paginator
    {
        return Song::query(type: PlayableType::SONG, user: $scopedUser ?? $this->auth->user())
            ->withUserContext()
            ->storedLocally()
            ->when($folder, static fn (SongBuilder $query) => $query->where('folder_id', $folder->id)) // @phpstan-ignore-line
            ->when(!$folder, static fn (SongBuilder $query) => $query->whereNull('folder_id'))
            ->orderBy('songs.path')
            ->simplePaginate(50);
    }

    /** @return Collection|array<array-key, Song> */
    public function getByArtist(Artist $artist, ?User $scopedUser = null): Collection
    {
        return Song::query(type: PlayableType::SONG, user: $scopedUser ?? $this->auth->user())
            ->withUserContext()
            ->where(static function (SongBuilder $query) use ($artist): void {
                $query->whereBelongsTo($artist)
                    ->orWhereHas('album', static function (Builder $albumQuery) use ($artist): void {
                        $albumQuery->whereBelongsTo($artist);
                    });
            })
            ->orderBy('songs.album_name')
            ->orderBy('songs.disc')
            ->orderBy('songs.track')
            ->orderBy('songs.title')
            ->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getByPlaylist(Playlist $playlist, ?User $scopedUser = null): Collection
    {
        if ($playlist->is_smart) {
            return $this->getBySmartPlaylist($playlist, $scopedUser);
        } else {
            return $this->getByStandardPlaylist($playlist, $scopedUser);
        }
    }

    /** @return Collection|array<array-key, Song> */
    private function getByStandardPlaylist(Playlist $playlist, ?User $scopedUser = null): Collection
    {
        throw_if($playlist->is_smart, new LogicException('Not a standard playlist.'));

        return Song::query(user: $scopedUser ?? $this->auth->user())
            ->withUserContext()
            ->leftJoin('playlist_song', 'songs.id', '=', 'playlist_song.song_id')
            ->leftJoin('playlists', 'playlists.id', '=', 'playlist_song.playlist_id')
            ->when(License::isPlus(), static function (SongBuilder $query): SongBuilder {
                return
                    $query->join('users as collaborators', 'playlist_song.user_id', '=', 'collaborators.id')
                        ->addSelect(
                            'collaborators.public_id as collaborator_public_id',
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
    private function getBySmartPlaylist(Playlist $playlist, ?User $scopedUser = null): Collection
    {
        throw_unless($playlist->is_smart, NonSmartPlaylistException::create($playlist));

        $query = Song::query(type: PlayableType::SONG, user: $scopedUser)->withUserContext();

        $playlist->rule_groups->each(static function (RuleGroup $group, int $index) use ($query): void {
            $whereClosure = static function (SongBuilder $subQuery) use ($group): void {
                $group->rules->each(static function (Rule $rule) use ($subQuery): void {
                    QueryModifier::applyRule($rule, $subQuery);
                });
            };

            $query->when(
                $index === 0,
                static fn (SongBuilder $query) => $query->where($whereClosure),
                static fn (SongBuilder $query) => $query->orWhere($whereClosure)
            );
        });

        return $query->orderBy('songs.title')
            ->limit(self::LIST_SIZE_LIMIT)
            ->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getRandom(int $limit, ?User $scopedUser = null): Collection
    {
        return Song::query(type: PlayableType::SONG, user: $scopedUser ?? $this->auth->user())
            ->withUserContext()
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /** @return Collection|array<array-key, Song> */
    public function getMany(array $ids, bool $preserveOrder = false, ?User $scopedUser = null): Collection
    {
        $songs = Song::query(user: $scopedUser ?? $this->auth->user())
            ->withUserContext()
            ->whereIn('songs.id', $ids)
            ->get();

        return $preserveOrder ? $songs->orderByArray($ids) : $songs; // @phpstan-ignore-line
    }

    /** @param array<string> $ids */
    public function countAccessibleByIds(array $ids, ?User $scopedUser = null): int
    {
        return Song::query(user: $scopedUser ?? $this->auth->user())
            ->accessible()
            ->whereIn('songs.id', $ids)
            ->count();
    }

    /**
     * Gets several songs, but also includes collaborative information.
     *
     * @return Collection|array<array-key, Song>
     */
    public function getManyInCollaborativeContext(array $ids, ?User $scopedUser = null): Collection
    {
        return Song::query(user: $scopedUser ?? $this->auth->user())
            ->withUserContext()
            ->when(License::isPlus(), static function (SongBuilder $query): SongBuilder {
                return
                    $query->leftJoin('playlist_song', 'songs.id', '=', 'playlist_song.song_id')
                        ->leftJoin('playlists', 'playlists.id', '=', 'playlist_song.playlist_id')
                        ->join('users as collaborators', 'playlist_song.user_id', '=', 'collaborators.id')
                        ->addSelect(
                            'collaborators.public_id as collaborator_public_id',
                            'collaborators.name as collaborator_name',
                            'collaborators.email as collaborator_email',
                            'playlist_song.created_at as added_at'
                        );
            })
            ->whereIn('songs.id', $ids)
            ->get();
    }

    /** @param string $id */
    public function getOne($id, ?User $user = null): Song
    {
        return Song::query(user: $user ?? $this->auth->user())
            ->withUserContext()
            ->findOrFail($id);
    }

    /** @param string $id */
    public function findOne($id, ?User $user = null): ?Song
    {
        return Song::query(user: $user ?? $this->auth->user())
            ->withUserContext()
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

    /**
     * @param Genre|null $genre If null, query songs that have no genre.
     *
     * @return Collection|array<array-key, Song>
     */
    public function getByGenre(?Genre $genre, int $limit, $random = false, ?User $scopedUser = null): Collection
    {

        return Song::query(type: PlayableType::SONG, user: $scopedUser ?? $this->auth->user())
            ->withUserContext()
            ->when($genre, static fn (Builder $builder) => $builder->whereRelation('genres', 'genres.id', $genre->id))
            ->when(!$genre, static fn (Builder $builder) => $builder->whereDoesntHave('genres'))
            ->when($random, static fn (Builder $builder) => $builder->inRandomOrder())
            ->when(!$random, static fn (Builder $builder) => $builder->orderBy('songs.title'))
            ->limit($limit)
            ->get();
    }

    /** @return array<string> */
    public function getEpisodeGuidsByPodcast(Podcast $podcast): array
    {
        return $podcast->episodes()->pluck('episode_guid')->toArray();
    }

    /** @return Collection<Song>|array<array-key, Song> */
    public function getEpisodesByPodcast(Podcast $podcast, ?User $user = null): Collection
    {
        return Song::query(user: $user ?? $this->auth->user())
            ->withUserContext()
            ->whereBelongsTo($podcast)
            ->orderByDesc('songs.created_at')
            ->get();
    }

    /** @return Collection<Song>|array<array-key, Song> */
    public function getUnderPaths(
        array $paths,
        int $limit = 500,
        bool $random = false,
        ?User $scopedUser = null
    ): Collection {
        $paths = array_map(static fn (?string $path) => $path ? trim($path, DIRECTORY_SEPARATOR) : '', $paths);

        if (!$paths) {
            return Collection::empty();
        }

        $hasRootPath = in_array('', $paths, true);
        $scopedUser ??= $this->auth->user();

        return Song::query(type: PlayableType::SONG, user: $scopedUser)
            ->withUserContext()
            // if the root path is included, we don't need to filter by folder
            ->when(!$hasRootPath, function (SongBuilder $query) use ($paths, $scopedUser): void {
                $folders = $this->folderRepository->getByPaths($paths, $scopedUser);
                $query->whereIn('songs.folder_id', $folders->pluck('id'));
            })
            ->when(!$random, static fn (SongBuilder $query): SongBuilder => $query->orderBy('songs.path'))
            ->when($random, static fn (SongBuilder $query): SongBuilder => $query->inRandomOrder())
            ->limit($limit)
            ->get();
    }

    /**
     * Fetch songs **directly** in a specific folder (or the media root if null).
     * This does not include songs in subfolders.
     *
     * @return Collection<Song>|array<array-key, Song>
     */
    public function getInFolder(?Folder $folder = null, int $limit = 500, ?User $scopedUser = null): Collection
    {
        return Song::query(type: PlayableType::SONG, user: $scopedUser ?? $this->auth->user())
            ->withUserContext()
            ->limit($limit)
            ->when($folder, static fn (SongBuilder $query) => $query->where('songs.folder_id', $folder->id)) // @phpstan-ignore-line
            ->when(!$folder, static fn (SongBuilder $query) => $query->whereNull('songs.folder_id'))
            ->orderBy('songs.path')
            ->get();
    }

    /** @return Collection<Song>|array<array-key, Song> */
    public function search(string $keywords, int $limit, ?User $user = null): Collection
    {
        return $this->getMany(
            ids: Song::search($keywords)->get()->take($limit)->modelKeys(),
            preserveOrder: true,
            scopedUser: $user,
        );
    }

    /** @return Collection<Song>|array<array-key, Song> */
    public function getForEmbed(Embed $embed): Collection
    {
        throw_unless((bool) $embed->embeddable, new EmbeddableNotFoundException());

        return match (EmbeddableType::from($embed->embeddable_type)) {
            EmbeddableType::ALBUM => $this->getByAlbum($embed->embeddable, $embed->user),
            EmbeddableType::ARTIST => $this->getByArtist($embed->embeddable, $embed->user),
            EmbeddableType::PLAYLIST => $this->getByPlaylist($embed->embeddable, $embed->user),
            EmbeddableType::PLAYABLE => $this->getMany(ids: [$embed->embeddable->getKey()], scopedUser: $embed->user),
        };
    }
}
