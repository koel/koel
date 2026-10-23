<?php

namespace App\Repositories;

use App\Builders\AlbumBuilder;
use App\Models\Album;
use App\Models\Artist;
use App\Models\User;
use App\Repositories\Contracts\PaginationStrategy;
use App\Repositories\Contracts\ScoutableRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

/**
 * @extends Repository<Album>
 * @implements ScoutableRepository<Album>
 */
// @mago-ignore lint:too-many-methods
class AlbumRepository extends Repository implements ScoutableRepository
{
    private const int RANDOM_SONGS_SAMPLED_PER_ALBUM = 4;

    public function isAccessibleByUser(Album $album, User $user): bool
    {
        return Album::query()->whereAccessibleByUser($user)->whereKey($album->id)->exists();
    }

    /** @return LazyCollection<array-key, Album> */
    public function lazyGetWithIncompleteMbids(): LazyCollection
    {
        return self::queryWithIncompleteMbids()->lazyById();
    }

    public function countWithIncompleteMbids(): int
    {
        return self::queryWithIncompleteMbids()->count();
    }

    /** @return LazyCollection<array-key, Album> */
    public function lazyGetWithoutSmallCover(): LazyCollection
    {
        return self::queryWithoutSmallCover()->lazyById();
    }

    public function countWithoutSmallCover(): int
    {
        return self::queryWithoutSmallCover()->count();
    }

    public function countAccessibleByUser(User $user): int
    {
        return Album::query()->onlyStandard()->setScopedUser($user)->accessible()->count();
    }

    private static function queryWithoutSmallCover(): AlbumBuilder
    {
        return Album::query()->where('albums.cover', '<>', '')->whereNull('albums.small_cover');
    }

    /**
     * An album is incomplete while it lacks an identifier of its own or any of its songs lacks one. A
     * tagged file commonly names the release without naming the recording, so the two run out separately.
     */
    private static function queryWithIncompleteMbids(): AlbumBuilder
    {
        return Album::query()
            ->onlyStandard()
            ->where(static fn (AlbumBuilder $query) => $query->whereNull(
                'albums.mbid',
            )->orWhereHas('songs', static fn (Builder $songs) => $songs->whereNull('songs.mbid')));
    }

    /**
     * @param string $id
     */
    public function getOne($id, ?User $user = null): Album
    {
        return Album::query()
            ->withUserContext(user: $user ?? $this->auth->user())
            ->findOrFail($id);
    }

    /** @param string $id */
    public function findOne($id, ?User $user = null): ?Album
    {
        return Album::query()
            ->withUserContext(user: $user ?? $this->auth->user())
            ->find($id);
    }

    public function getRecentlyAdded(int $count = 6, int $offset = 0, ?User $user = null): Collection
    {
        $user ??= $this->auth->user();

        $ids = Album::query()
            ->select('albums.id')
            ->onlyStandard()
            ->setScopedUser($user)
            ->accessible()
            ->orderByDesc('albums.created_at')
            ->orderBy('albums.id')
            ->offset($offset)
            ->limit($count)
            ->pluck('albums.id');

        return $this->getMany($ids->all(), preserveOrder: true, user: $user);
    }

    public function getMostPlayed(int $count = 6, int $offset = 0, ?User $user = null): Collection
    {
        $user ??= $this->auth->user();

        $ids = Album::query()
            ->select('albums.id')
            ->onlyStandard()
            ->setScopedUser($user)
            ->accessible()
            ->withPlayCount(playedOnly: true)
            ->orderByDesc('play_count')
            ->orderBy('albums.id')
            ->offset($offset)
            ->limit($count)
            ->pluck('albums.id');

        return $this->getMany($ids->all(), preserveOrder: true, user: $user);
    }

    public function getRecentlyPlayed(int $count = 6, int $offset = 0, ?User $user = null): Collection
    {
        $user ??= $this->auth->user();

        $lastPlayedAtByAlbum = DB::table('interactions')
            ->join('songs as recently_played_songs', 'recently_played_songs.id', 'interactions.song_id')
            ->where('interactions.user_id', $user->id)
            ->whereNotNull('interactions.last_played_at')
            ->groupBy('recently_played_songs.album_id')
            ->select('recently_played_songs.album_id')
            ->selectRaw('MAX(interactions.last_played_at) as last_played_at');

        $ids = Album::query()
            ->select('albums.id')
            ->onlyStandard()
            ->setScopedUser($user)
            ->accessible()
            ->joinSub($lastPlayedAtByAlbum, 'album_last_played', 'album_last_played.album_id', 'albums.id')
            ->orderByDesc('album_last_played.last_played_at')
            ->offset($offset)
            ->limit($count)
            ->pluck('albums.id');

        return $this->getMany($ids->all(), preserveOrder: true, user: $user);
    }

    /** @return Collection<int, Album> */
    public function getByYearRange(
        int $fromYear,
        int $toYear,
        int $size,
        int $offset = 0,
        ?User $user = null,
    ): Collection {
        $reverse = $fromYear > $toYear;
        $low = $reverse ? $toYear : $fromYear;
        $high = $reverse ? $fromYear : $toYear;

        return Album::query()
            ->onlyStandard()
            ->withUserContext(user: $user ?? $this->auth->user())
            ->whereBetween('albums.year', [$low, $high])
            ->orderBy('albums.year', $reverse ? 'desc' : 'asc')
            ->orderBy('albums.name')
            ->offset($offset)
            ->limit($size)
            ->get();
    }

    public function getByGenre(string $genreName, int $size, int $offset = 0, ?User $user = null): Collection
    {
        return Album::query()
            ->onlyStandard()
            ->withUserContext(user: $user ?? $this->auth->user())
            ->whereIn(
                'albums.id',
                DB::table('songs')
                    ->join('genre_song', 'genre_song.song_id', 'songs.id')
                    ->join('genres', 'genres.id', 'genre_song.genre_id')
                    ->where('genres.name', $genreName)
                    ->select('songs.album_id'),
            )
            ->orderBy('albums.name')
            ->orderBy('albums.id')
            ->offset($offset)
            ->limit($size)
            ->get();
    }

    public function getHighestRated(int $size, int $offset = 0, ?User $user = null): Collection
    {
        $user ??= $this->auth->user();

        return Album::query()
            ->onlyStandard()
            ->withUserContext(user: $user)
            ->join('ratings', static function (JoinClause $join) use ($user): void {
                $join->on('ratings.rateable_id', '=', 'albums.id')->where('ratings.rateable_type', 'album')->where(
                    'ratings.user_id',
                    $user->id,
                );
            })
            ->orderByDesc('ratings.rating')
            ->orderBy('albums.name')
            ->orderBy('albums.id')
            ->offset($offset)
            ->limit($size)
            ->get();
    }

    /**
     * The user's albums that songs owned by others still use.
     *
     * @return Collection<int, Album>
     */
    public function getOwnedByUserButUsedByOthers(User $user): Collection
    {
        return Album::query()
            ->where('user_id', $user->id)
            ->whereHas('songs', static fn (Builder $songs) => $songs->where('owner_id', '<>', $user->id))
            ->get();
    }

    public function getMany(array $ids, bool $preserveOrder = false, ?User $user = null): Collection
    {
        $albums = Album::query()
            ->onlyStandard()
            ->withUserContext(user: $user ?? $this->auth->user())
            ->getWhereInChunks('albums.id', $ids);

        return $preserveOrder ? $albums->orderByArray($ids) : $albums;
    }

    /** @return Collection<int, Album> */
    public function getByArtist(Artist $artist, ?User $user = null): Collection
    {
        $albumIdsOfArtistSongs = DB::table('songs')->select('songs.album_id')->where('songs.artist_id', $artist->id);

        $albumIds = DB::table('albums')
            ->select('albums.id')
            ->where('albums.artist_id', $artist->id)
            ->union($albumIdsOfArtistSongs);

        return Album::query()
            ->withUserContext(user: $user ?? $this->auth->user())
            ->whereIn('albums.id', $albumIds)
            ->orderBy('albums.name')
            ->get();
    }

    /** @return Collection<int, Album> */
    public function getFavorites(?int $limit = null, int $offset = 0, ?User $user = null): Collection
    {
        return Album::query()
            ->onlyStandard()
            ->withUserContext(user: $user ?? $this->auth->user(), favoritesOnly: true)
            ->orderBy('favorites.position')
            ->when($offset > 0, static fn (Builder $query) => $query->offset($offset))
            ->when($limit !== null, static fn (Builder $query) => $query->limit($limit))
            ->get();
    }

    /** @return Collection<int, Album> */
    public function getRandom(int $limit, ?User $user = null): Collection
    {
        $user ??= $this->auth->user();

        $ids = collect();

        do {
            $missingCount = $limit - $ids->count();

            $newIdsOfRandomSongs = self::pluckFromRandomHash(
                Album::query()
                    ->select('albums.id')
                    ->onlyStandard()
                    ->setScopedUser($user)
                    ->accessible()
                    ->whereNotIn('albums.id', $ids->all())
                    ->join('songs as random_songs', 'random_songs.album_id', 'albums.id'),
                hashColumn: 'random_songs.path_hash',
                idColumn: 'albums.id',
                limit: $missingCount * self::RANDOM_SONGS_SAMPLED_PER_ALBUM,
            );

            $ids = $ids->merge($newIdsOfRandomSongs->unique()->take($missingCount));
        } while ($newIdsOfRandomSongs->isNotEmpty() && $ids->count() < $limit);

        return $this->getMany($ids->shuffle()->all(), preserveOrder: true, user: $user);
    }

    /** @return Collection<int, Album> */
    public function getOrdered(
        string $sortColumn,
        string $sortDirection,
        int $limit,
        int $offset = 0,
        ?User $user = null,
    ): Collection {
        return Album::query()
            ->onlyStandard()
            ->withUserContext(user: $user ?? $this->auth->user())
            ->sort($sortColumn, $sortDirection)
            ->orderBy('albums.id')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function paginate(
        string $sortColumn,
        string $sortDirection,
        PaginationStrategy $strategy,
        bool $favoritesOnly = false,
        ?User $user = null,
    ): Paginator|CursorPaginator {
        return $strategy->apply(
            Album::query()
                ->onlyStandard()
                ->withUserContext(user: $user ?? $this->auth->user(), favoritesOnly: $favoritesOnly)
                ->sort($sortColumn, $sortDirection),
            idColumn: 'albums.id',
            perPage: 60,
        );
    }

    public function search(string $keywords, int $limit, ?User $user = null): Collection
    {
        return $this->getMany(
            ids: Album::search($keywords)->take(self::SEARCH_CANDIDATE_LIMIT)->keys()->all(),
            preserveOrder: true,
            user: $user,
        )->take($limit);
    }
}
