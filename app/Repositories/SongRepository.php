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
use App\Models\Organization;
use App\Models\Playlist;
use App\Models\Podcast;
use App\Models\Song;
use App\Models\User;
use App\Providers\MacroProvider;
use App\Repositories\Contracts\PaginationStrategy;
use App\Repositories\Contracts\ScoutableRepository;
use App\Values\SmartPlaylist\SmartPlaylistQueryModifier as QueryModifier;
use App\Values\SmartPlaylist\SmartPlaylistRule as Rule;
use App\Values\SmartPlaylist\SmartPlaylistRuleGroup as RuleGroup;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use LogicException;

/**
 * @extends Repository<Song>
 * @implements ScoutableRepository<Song>
 */
// @mago-ignore lint:too-many-methods,cyclomatic-complexity
class SongRepository extends Repository implements ScoutableRepository
{
    private const int LIST_SIZE_LIMIT = 500;

    public function __construct(
        private readonly AlbumRepository $albumRepository,
        private readonly ArtistRepository $artistRepository,
        private readonly PlaylistRepository $playlistRepository,
        private readonly FolderRepository $folderRepository,
    ) {
        parent::__construct();
    }

    public function findOneByPath(string $path): ?Song
    {
        return Song::query()->wherePath($path)->first();
    }

    public function findMtimeByPath(string $path): ?int
    {
        return Song::query()
            ->withoutEagerLoads()
            ->wherePath($path)
            ->select('songs.mtime')
            ->first()
            ?->mtime;
    }

    /** @return LazyCollection<int, string> */
    public function lazyGetIdsByOwner(User $owner): LazyCollection
    {
        return Song::query()
            ->withoutEagerLoads()
            ->whereBelongsTo($owner, 'owner')
            ->select('songs.id')
            ->lazyById(column: 'songs.id', alias: 'id')
            ->map(static fn (Song $song): string => $song->id);
    }

    /**
     * @param array<string> $ids
     *
     * @return Collection<int, Song>
     */
    public function getManyForFileRemoval(array $ids): Collection
    {
        return Song::query()
            ->withoutEagerLoads()
            ->whereKey($ids)
            ->select('songs.id', 'songs.path', 'songs.storage')
            ->get();
    }

    /**
     * @param array<string> $paths
     *
     * @return Collection<int, Song>
     */
    public function getManyByPaths(array $paths): Collection
    {
        return Song::query()->wherePathIn($paths)->get();
    }

    /** @param array<string> $hashes */
    public function findByAnyHash(array $hashes, User $owner): ?Song
    {
        return Song::query()->whereIn('hash', $hashes)->where('owner_id', $owner->id)->first();
    }

    public function findOneByArtistAndTitle(?string $artist, ?string $title, ?User $scopedUser = null): ?Song
    {
        $hasTitle = $title !== null && $title !== '';
        $hasArtist = $artist !== null && $artist !== '';

        if (!$hasTitle && !$hasArtist) {
            return null;
        }

        return Song::query(type: PlayableType::SONG, user: $scopedUser ?? $this->auth->user())
            ->withUserContext()
            ->when($hasTitle, static fn (Builder $query) => $query->where('songs.title', $title))
            ->when($hasArtist, static fn (Builder $query) => $query->whereHas('artist', static fn (Builder $artistQuery) => $artistQuery->where(
                'name',
                $artist,
            )))
            ->first();
    }

    public function hasLocallyStoredSongs(): bool
    {
        return Song::query()->storedLocally()->exists();
    }

    public function getRecentlyAdded(int $count = 8, ?User $scopedUser = null): Collection
    {
        $scopedUser ??= $this->auth->user();

        $ids = Song::query(type: PlayableType::SONG, user: $scopedUser)
            ->select('songs.id')
            ->accessible()
            ->orderByDesc('songs.created_at')
            ->limit($count)
            ->pluck('songs.id');

        return $this->getMany($ids->all(), preserveOrder: true, scopedUser: $scopedUser);
    }

    public function getMostPlayed(int $count = 8, ?User $scopedUser = null, ?PlayableType $type = null): Collection
    {
        return Song::query(type: $type, user: $scopedUser ?? $this->auth->user())
            ->withUserContext()
            ->where('interactions.play_count', '>', 0)
            ->orderByDesc('interactions.play_count')
            ->limit($count)
            ->get();
    }

    public function getLeastPlayed(int $count = 50, ?User $scopedUser = null, ?PlayableType $type = null): Collection
    {
        $scopedUser ??= $this->auth->user();

        $unplayedIds = Song::query(type: $type, user: $scopedUser)
            ->select('songs.id')
            ->accessible()
            ->whereNotExists(static function (QueryBuilder $query) use ($scopedUser): void {
                $query
                    ->select(DB::raw(1))
                    ->from('interactions')
                    ->whereColumn('interactions.song_id', 'songs.id')
                    ->where('interactions.user_id', $scopedUser->id)
                    ->where('interactions.play_count', '>', 0);
            })
            ->limit($count)
            ->pluck('songs.id');

        $remainingCount = $count - $unplayedIds->count();

        $leastPlayedIds = $remainingCount > 0
            ? Song::query(type: $type, user: $scopedUser)
                ->select('songs.id')
                ->accessible()
                ->join('interactions', static function (JoinClause $join) use ($scopedUser): void {
                    $join->on('interactions.song_id', 'songs.id')->where('interactions.user_id', $scopedUser->id);
                })
                ->where('interactions.play_count', '>', 0)
                ->orderBy('interactions.play_count')
                ->limit($remainingCount)
                ->pluck('songs.id')
            : collect();

        return $this->getMany(
            [...$unplayedIds->all(), ...$leastPlayedIds->all()],
            preserveOrder: true,
            scopedUser: $scopedUser,
        );
    }

    public function getRecentlyPlayed(int $count = 8, ?User $scopedUser = null, ?PlayableType $type = null): Collection
    {
        return Song::query(type: $type, user: $scopedUser ?? $this->auth->user())
            ->withUserContext()
            ->where('interactions.play_count', '>', 0)
            ->orderByDesc('interactions.last_played_at')
            ->limit($count)
            ->get();
    }

    public function paginate(
        array $sortColumns,
        string $sortDirection,
        PaginationStrategy $strategy,
        ?User $scopedUser = null,
    ): Paginator|CursorPaginator {
        return $strategy->apply(
            Song::query(type: PlayableType::SONG, user: $scopedUser ?? $this->auth->user())
                ->withUserContext()
                ->sort($sortColumns, $sortDirection),
            idColumn: 'songs.id',
            perPage: 50,
        );
    }

    /**
     * @param Genre|null $genre If null, paginate songs that have no genre
     */
    public function paginateByGenre(
        ?Genre $genre,
        array $sortColumns,
        string $sortDirection,
        PaginationStrategy $strategy,
        ?User $scopedUser = null,
    ): Paginator|CursorPaginator {
        return $strategy->apply(
            Song::query(type: PlayableType::SONG, user: $scopedUser ?? $this->auth->user())
                ->withUserContext()
                ->when($genre, static fn (Builder $builder) => $builder->whereRelation(
                    'genres',
                    'genres.id',
                    $genre->id,
                ))
                ->when(!$genre, static fn (SongBuilder $builder) => $builder->genreless())
                ->sort($sortColumns, $sortDirection),
            idColumn: 'songs.id',
            perPage: 50,
        );
    }

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

    /** @return Collection<int, Song> */
    public function getOrderedById(int $limit, int $offset = 0, ?User $scopedUser = null): Collection
    {
        $scopedUser ??= $this->auth->user();

        $ids = Song::query(type: PlayableType::SONG, user: $scopedUser)
            ->select('songs.id')
            ->accessible()
            ->orderBy('songs.id')
            ->offset($offset)
            ->limit($limit)
            ->pluck('songs.id')
            ->all();

        return $this->getMany(ids: $ids, preserveOrder: true, scopedUser: $scopedUser);
    }

    /** @return Collection<int, Song> */
    public function getFavorites(?User $scopedUser = null, ?PlayableType $type = null): Collection
    {
        return Song::query(type: $type, user: $scopedUser ?? $this->auth->user())
            ->withUserContext(favoritesOnly: true)
            ->orderBy('favorites.position')
            ->get();
    }

    /** @return Collection<int, Song> */
    public function getByAlbum(Album|string $album, ?User $scopedUser = null): Collection
    {
        $album = $this->albumRepository->resolveOne($album);

        return Song::query(user: $scopedUser ?? $this->auth->user())
            ->withUserContext()
            ->whereBelongsTo($album)
            ->orderBy('songs.disc')
            ->orderBy('songs.track')
            ->orderBy('songs.title')
            ->get();
    }

    public function paginateInFolder(?string $cursor, ?Folder $folder = null, ?User $scopedUser = null): CursorPaginator
    {
        return Song::query(type: PlayableType::SONG, user: $scopedUser ?? $this->auth->user())
            ->withUserContext()
            ->storedLocally()
            ->when($folder, static fn (SongBuilder $query) => $query->where('folder_id', $folder->id)) // @phpstan-ignore-line
            ->when(!$folder, static fn (SongBuilder $query) => $query->whereNull('folder_id'))
            ->orderBy('songs.path')
            ->orderBy('songs.id')
            ->cursorPaginate(50, cursor: $cursor);
    }

    public function getByArtist(Artist|string $artist, ?User $scopedUser = null): Collection
    {
        $artist = $this->artistRepository->resolveOne($artist);

        $songsOnArtistAlbums = DB::table('songs')
            ->select('songs.id')
            ->join('albums', 'albums.id', 'songs.album_id')
            ->where('albums.artist_id', $artist->id);

        $songIds = DB::table('songs')
            ->select('songs.id')
            ->where('songs.artist_id', $artist->id)
            ->union($songsOnArtistAlbums);

        return Song::query(type: PlayableType::SONG, user: $scopedUser ?? $this->auth->user())
            ->withUserContext()
            ->whereIn('songs.id', $songIds)
            ->orderBy('songs.album_name')
            ->orderBy('songs.disc')
            ->orderBy('songs.track')
            ->orderBy('songs.title')
            ->get();
    }

    /** @return Collection<int, Song> */
    public function getByPlaylist(Playlist|string $playlist, ?User $scopedUser = null): Collection
    {
        $playlist = $this->playlistRepository->resolveOne($playlist);

        if ($playlist->is_smart) {
            return $this->getBySmartPlaylist($playlist, $scopedUser);
        } else {
            return $this->getByStandardPlaylist($playlist, $scopedUser);
        }
    }

    private function getByStandardPlaylist(Playlist $playlist, ?User $scopedUser = null): Collection
    {
        throw_if($playlist->is_smart, new LogicException('Not a standard playlist.'));

        return Song::query(user: $scopedUser ?? $this->auth->user())
            ->withUserContext()
            ->leftJoin('playlist_song', 'songs.id', '=', 'playlist_song.song_id')
            ->leftJoin('playlists', 'playlists.id', '=', 'playlist_song.playlist_id')
            ->when(License::isPlus(), static function (SongBuilder $query): SongBuilder {
                return $query->join(
                    'users as collaborators',
                    'playlist_song.user_id',
                    '=',
                    'collaborators.id',
                )->addSelect(
                    'collaborators.public_id as collaborator_public_id',
                    'collaborators.name as collaborator_name',
                    'collaborators.email as collaborator_email',
                    'collaborators.avatar as collaborator_avatar',
                    'playlist_song.created_at as added_at',
                );
            })
            ->where('playlists.id', $playlist->id)
            ->orderBy('playlist_song.position')
            ->get();
    }

    private function getBySmartPlaylist(Playlist $playlist, ?User $scopedUser = null): Collection
    {
        throw_unless($playlist->is_smart, NonSmartPlaylistException::create($playlist));

        $query = Song::query(type: PlayableType::SONG, user: $scopedUser ?? $this->auth->user())->withUserContext();

        return self::limitToSmartPlaylistRules($query, $playlist)
            ->orderBy('songs.title')
            ->orderBy('songs.id')
            ->limit(self::LIST_SIZE_LIMIT)
            ->get();
    }

    /**
     * Count and total the length of the songs a smart playlist returns, without loading them.
     *
     * @return array{count: int, length: float}
     */
    public function getSmartPlaylistStats(Playlist $playlist, User $user): array
    {
        throw_unless($playlist->is_smart, NonSmartPlaylistException::create($playlist));

        $matchingSongs = self::limitToSmartPlaylistRules(
            Song::query(type: PlayableType::SONG, user: $user)->withUserContext(includeFavoriteStatus: false),
            $playlist,
        )
            ->select('songs.length')
            ->orderBy('songs.title')
            ->orderBy('songs.id')
            ->limit(self::LIST_SIZE_LIMIT);

        $stats = DB::query()
            ->fromSub($matchingSongs, 'matching_songs')
            ->selectRaw('COUNT(*) as song_count, COALESCE(SUM(length), 0) as total_length')
            ->first();

        return [
            'count' => (int) $stats->song_count,
            'length' => (float) $stats->total_length,
        ];
    }

    private static function limitToSmartPlaylistRules(SongBuilder $query, Playlist $playlist): SongBuilder
    {
        return $query->where(static function (SongBuilder $anyGroupQuery) use ($playlist): void {
            $playlist->rule_groups->each(static function (RuleGroup $group) use ($anyGroupQuery): void {
                $anyGroupQuery->orWhere(static function (SongBuilder $groupQuery) use ($group): void {
                    $group->rules->each(static fn (Rule $rule) => QueryModifier::applyRule($rule, $groupQuery));
                });
            });
        });
    }

    /** @return Collection<int, Song> */
    public function getRandomInGenreAndYears(
        int $limit,
        ?Genre $genre,
        ?int $fromYear,
        ?int $toYear,
        ?User $scopedUser = null,
    ): Collection {
        $scopedUser ??= $this->auth->user();

        $ids = Song::query(type: PlayableType::SONG, user: $scopedUser)
            ->select('songs.id')
            ->accessible()
            ->when($genre, static fn (SongBuilder $query) => $query->whereIn(
                'songs.id',
                $genre->songs()->newPivotQuery()->select('song_id'),
            ))
            ->when($fromYear !== null, static fn (SongBuilder $query) => $query->where('songs.year', '>=', $fromYear))
            ->when($toYear !== null, static fn (SongBuilder $query) => $query->where('songs.year', '<=', $toYear))
            ->inRandomOrder()
            ->limit($limit)
            ->pluck('songs.id');

        return $this->getMany($ids->all(), preserveOrder: true, scopedUser: $scopedUser);
    }

    /** @return Collection<int, Song> */
    public function getRandom(int $limit, ?User $scopedUser = null): Collection
    {
        $scopedUser ??= $this->auth->user();

        $ids = self::pluckFromRandomHash(
            Song::query(type: PlayableType::SONG, user: $scopedUser)->select('songs.id')->accessible(),
            hashColumn: 'songs.path_hash',
            idColumn: 'songs.id',
            limit: $limit,
        );

        return $this->getMany($ids->shuffle()->all(), preserveOrder: true, scopedUser: $scopedUser);
    }

    /**
     * IDs of the user's songs that came from a library scan rather than an upload.
     *
     * @return array<string>
     */
    public function getIdsOfScannedSongsOwnedBy(User $user): array
    {
        return Song::query()
            ->where('owner_id', $user->id)
            ->storedLocally()
            ->whereNot('path', 'like', '%__KOEL_UPLOADS_%')
            ->pluck('id')
            ->all();
    }

    public function getMany(array $ids, bool $preserveOrder = false, ?User $scopedUser = null): Collection
    {
        $songs = Song::query(user: $scopedUser ?? $this->auth->user())
            ->withUserContext()
            ->getWhereInChunks('songs.id', $ids);

        return $preserveOrder ? $songs->orderByArray($ids) : $songs; // @phpstan-ignore-line
    }

    /** @param array<string> $ids */
    public function countAccessibleByIds(array $ids, ?User $scopedUser = null): int
    {
        return Song::query(user: $scopedUser ?? $this->auth->user())
            ->accessible()
            ->countWhereInChunks('songs.id', $ids);
    }

    /**
     * Gets several songs, but also includes collaborative information.
     */
    public function getManyInCollaborativeContext(Playlist $playlist, array $ids, ?User $scopedUser = null): Collection
    {
        return Song::query(user: $scopedUser ?? $this->auth->user())
            ->withUserContext()
            ->when(License::isPlus(), static function (SongBuilder $query) use ($playlist): SongBuilder {
                return $query
                    ->leftJoin('playlist_song', static function (JoinClause $join) use ($playlist): void {
                        $join->on('songs.id', '=', 'playlist_song.song_id')->where(
                            'playlist_song.playlist_id',
                            $playlist->id,
                        );
                    })
                    ->leftJoin('playlists', 'playlists.id', '=', 'playlist_song.playlist_id')
                    ->join('users as collaborators', 'playlist_song.user_id', '=', 'collaborators.id')
                    ->addSelect(
                        'collaborators.public_id as collaborator_public_id',
                        'collaborators.name as collaborator_name',
                        'collaborators.email as collaborator_email',
                        'collaborators.avatar as collaborator_avatar',
                        'playlist_song.created_at as added_at',
                    );
            })
            ->getWhereInChunks('songs.id', $ids);
    }

    /** @param string $id */
    public function getOne($id, ?User $user = null): Song
    {
        return Song::query(user: $user ?? $this->auth->user())
            ->withUserContext()
            ->findOrFail($id);
    }

    public function getOneWithoutRelations(string $id): Song
    {
        return Song::query()->withoutEagerLoads()->findOrFail($id);
    }

    public function getOneAccessibleWithoutRelations(string $id, ?User $user = null): Song
    {
        return Song::query(user: $user ?? $this->auth->user())
            ->accessible()
            ->withoutEagerLoads()
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
        return Song::query(type: PlayableType::SONG, user: $scopedUser ?? $this->auth->user())->accessible()->sum(
            'length',
        );
    }

    /**
     * @param Genre|null $genre If null, query songs that have no genre.
     * @return Collection<int, Song>
     */
    public function getByGenre(
        ?Genre $genre,
        int $limit,
        bool $random = false,
        ?User $scopedUser = null,
        int $offset = 0,
    ): Collection {
        $scopedUser ??= $this->auth->user();

        if ($random) {
            return $genre
                ? $this->getRandomByGenre($genre, $limit, $scopedUser)
                : $this->getRandomGenreless($limit, $scopedUser);
        }

        return Song::query(type: PlayableType::SONG, user: $scopedUser)
            ->withUserContext()
            ->when($genre, static fn (Builder $builder) => $builder->whereRelation('genres', 'genres.id', $genre->id))
            ->when(!$genre, static fn (SongBuilder $builder) => $builder->genreless())
            ->orderBy('songs.title')
            ->orderBy('songs.id')
            ->when($offset > 0, static fn (Builder $builder) => $builder->offset($offset))
            ->limit($limit)
            ->get();
    }

    /** @return Collection<int, Song> */
    private function getRandomByGenre(Genre $genre, int $limit, User $scopedUser): Collection
    {
        $ids = Song::query(type: PlayableType::SONG, user: $scopedUser)
            ->select('songs.id')
            ->accessible()
            ->whereIn('songs.id', $genre->songs()->newPivotQuery()->select('song_id'))
            ->inRandomOrder()
            ->limit($limit)
            ->pluck('songs.id');

        return $this->getMany($ids->all(), preserveOrder: true, scopedUser: $scopedUser);
    }

    /** @return Collection<int, Song> */
    private function getRandomGenreless(int $limit, User $scopedUser): Collection
    {
        $ids = $this->pickGenrelessIdsFromRandomWindow($limit, $scopedUser);

        if ($ids->count() < $limit) {
            $ids = Song::query(type: PlayableType::SONG, user: $scopedUser)
                ->select('songs.id')
                ->accessible()
                ->genreless()
                ->inRandomOrder()
                ->limit($limit)
                ->pluck('songs.id');
        }

        return $this->getMany($ids->all(), preserveOrder: true, scopedUser: $scopedUser);
    }

    /** @return SupportCollection<int, string> */
    private function pickGenrelessIdsFromRandomWindow(int $limit, User $scopedUser): SupportCollection
    {
        $candidateIds = self::pluckFromRandomHash(
            Song::query(type: PlayableType::SONG, user: $scopedUser)->select('songs.id')->accessible(),
            hashColumn: 'songs.path_hash',
            idColumn: 'songs.id',
            limit: $limit * 20,
        );

        $genrelessSongs = Song::query()->select('songs.id')->genreless();

        return $candidateIds
            ->lazy()
            ->chunk(MacroProvider::WHERE_IN_CHUNK_SIZE)
            ->flatMap(
                static fn (LazyCollection $chunk): SupportCollection => (clone $genrelessSongs)
                    ->whereIn('songs.id', $chunk)
                    ->pluck('songs.id'),
            )
            ->take($limit)
            ->collect()
            ->shuffle();
    }

    /** @return array<string> */
    public function getEpisodeGuidsByPodcast(Podcast $podcast): array
    {
        return $podcast->episodes()->pluck('episode_guid')->toArray();
    }

    public function getEpisodesByPodcast(Podcast $podcast, ?User $user = null): Collection
    {
        return Song::query(user: $user ?? $this->auth->user())
            ->withUserContext()
            ->whereBelongsTo($podcast)
            ->orderByDesc('songs.created_at')
            ->get();
    }

    public function countEpisodesSubscribedByUser(User $user): int
    {
        return Song::query(type: PlayableType::PODCAST_EPISODE)
            ->whereHas('podcast.subscribers', static fn (Builder $query) => $query->where('users.id', $user->id))
            ->count();
    }

    public function getTotalSongFileSize(Organization $organization): int
    {
        $ownerInOrganization = static fn (Builder $owner) => $owner->whereBelongsTo($organization);

        return (int) Song::query(type: PlayableType::SONG)->whereHas('owner', $ownerInOrganization)->sum(
            'songs.file_size',
        );
    }

    /** @return LazyCollection<int, Song> */
    public function lazyGetSongsWithoutFileSize(): LazyCollection
    {
        return Song::query(type: PlayableType::SONG)
            ->withoutEagerLoads()
            ->whereNull('songs.file_size')
            ->lazyById(column: 'songs.id', alias: 'id');
    }

    public function countSongsWithoutFileSize(): int
    {
        return Song::query(type: PlayableType::SONG)->whereNull('songs.file_size')->count();
    }

    /** @return LazyCollection<int, Song> */
    public function lazyGetSongsWithoutPreview(): LazyCollection
    {
        return Song::query(type: PlayableType::SONG)
            ->withoutEagerLoads()
            ->whereDoesntHave('preview')
            ->lazyById(column: 'songs.id', alias: 'id');
    }

    public function countSongsWithoutPreview(): int
    {
        return Song::query(type: PlayableType::SONG)->whereDoesntHave('preview')->count();
    }

    /** @return Collection<int, Song> */
    public function getNewestEpisodesForUser(int $count, ?User $user = null): Collection
    {
        $user ??= $this->auth->user();

        return Song::query(type: PlayableType::PODCAST_EPISODE, user: $user)
            ->withUserContext()
            ->whereHas('podcast.subscribers', static fn (Builder $query) => $query->where('users.id', $user->id))
            ->orderByDesc('songs.created_at')
            ->limit($count)
            ->get();
    }

    public function getUnderPaths(
        array $paths,
        int $limit = 500,
        bool $random = false,
        ?User $scopedUser = null,
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
                $query->whereIn(
                    'songs.folder_id',
                    $this->folderRepository->getIdsIncludingDescendants($folders->modelKeys()),
                );
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

    public function searchByLyrics(string $lyrics, int $limit = 50, ?User $user = null): Collection
    {
        $query = Song::query(type: PlayableType::SONG, user: $user ?? $this->auth->user())->withUserContext();

        if (DB::getDriverName() === 'sqlite') {
            $query->where('songs.lyrics', 'like', '%' . $lyrics . '%');
        } else {
            $query->whereFullText('songs.lyrics', $lyrics);
        }

        return $query->limit($limit)->get();
    }

    public function getSimilar(Song $song, int $limit = 50, ?User $user = null): Collection
    {
        /** @var Collection<int, Song> $songs */
        $songs = new Collection([$song]);

        return $this->getSimilarToMany($songs, $limit, $user);
    }

    /** @param Collection<int, Song> $songs */
    public function getSimilarToMany(Collection $songs, int $limit = 50, ?User $user = null): Collection
    {
        if ($songs->isEmpty()) {
            return new Collection();
        }

        $songIds = $songs->pluck('id')->all();
        $artistIds = $songs->pluck('artist_id')->unique()->all();

        /** @var Collection<int, Song> $loadedSongs */
        $loadedSongs = $songs->load('genres');
        $genreIds = $loadedSongs
            ->flatMap(static fn (Song $song) => $song->genres->pluck('id'))
            ->unique()
            ->all();

        $user ??= $this->auth->user();

        $similarSongsQuery = Song::query(type: PlayableType::SONG, user: $user)
            ->select('songs.id')
            ->accessible()
            ->whereNotIn('songs.id', $songIds)
            ->where(static function (SongBuilder $query) use ($artistIds, $genreIds): void {
                $query->whereIn(
                    'songs.artist_id',
                    $artistIds,
                )->when($genreIds, static fn (SongBuilder $q) => $q->orWhereHas('genres', static fn (Builder $gq) => $gq->whereIn(
                    'genres.id',
                    $genreIds,
                )));
            });

        $similarSongIds = self::pluckFromRandomHash(
            $similarSongsQuery,
            hashColumn: 'songs.path_hash',
            idColumn: 'songs.id',
            limit: $limit,
        );

        return $this->getMany($similarSongIds->shuffle()->all(), preserveOrder: true, scopedUser: $user);
    }

    public function search(string $keywords, int $limit, ?User $user = null): Collection
    {
        $user ??= $this->auth->user();
        $candidateIds = Song::search($keywords)->take(self::SEARCH_CANDIDATE_LIMIT)->keys();

        $accessibleCandidateIds = Song::query(user: $user)
            ->select('songs.id')
            ->accessible()
            ->whereIn('songs.id', $candidateIds)
            ->pluck('songs.id');

        $resultIds = $candidateIds->intersect($accessibleCandidateIds)->take($limit)->values()->all();

        return $this->getMany(ids: $resultIds, preserveOrder: true, scopedUser: $user);
    }

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
