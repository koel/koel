<?php

namespace App\Repositories;

use App\Models\Artist;
use App\Models\User;
use App\Repositories\Contracts\PaginationStrategy;
use App\Repositories\Contracts\ScoutableRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;

/**
 * @extends Repository<Artist>
 * @implements ScoutableRepository<Artist>
 */
// @mago-ignore lint:too-many-methods
class ArtistRepository extends Repository implements ScoutableRepository
{
    public function isAccessibleByUser(Artist $artist, User $user): bool
    {
        return Artist::query()->whereAccessibleByUser($user)->whereKey($artist->id)->exists();
    }

    /**
     * @param string $id
     */
    public function getOne($id, ?User $user = null): Artist
    {
        return Artist::query()
            ->withUserContext(user: $user ?? $this->auth->user())
            ->findOrFail($id);
    }

    /** @param string $id */
    public function findOne($id, ?User $user = null): ?Artist
    {
        return Artist::query()
            ->withUserContext(user: $user ?? $this->auth->user())
            ->find($id);
    }

    public function getRecentlyAdded(int $count = 6, ?User $user = null): Collection
    {
        $user ??= $this->auth->user();

        $ids = Artist::query()
            ->select('artists.id')
            ->onlyStandard()
            ->onlyAlbumArtists()
            ->setScopedUser($user)
            ->accessible()
            ->orderByDesc('artists.created_at')
            ->limit($count)
            ->pluck('artists.id');

        return $this->getMany($ids->all(), preserveOrder: true, user: $user);
    }

    public function getMostPlayed(int $count = 6, ?User $user = null): Collection
    {
        $user ??= $this->auth->user();

        $ids = Artist::query()
            ->select('artists.id')
            ->onlyStandard()
            ->onlyAlbumArtists()
            ->setScopedUser($user)
            ->accessible()
            ->withPlayCount(playedOnly: true)
            ->orderByDesc('play_count')
            ->limit($count)
            ->pluck('artists.id');

        return $this->getMany($ids->all(), preserveOrder: true, user: $user);
    }

    /** @return Collection<int, Artist> */
    public function getRandom(int $limit, ?User $user = null): Collection
    {
        $user ??= $this->auth->user();

        $ids = Artist::query()
            ->select('artists.id')
            ->onlyStandard()
            ->onlyAlbumArtists()
            ->setScopedUser($user)
            ->accessible()
            ->inRandomOrder()
            ->limit($limit)
            ->pluck('artists.id');

        return $this->getMany($ids->all(), preserveOrder: true, user: $user);
    }

    /**
     * The user's artists that songs or albums owned by others still use.
     *
     * @return Collection<int, Artist>
     */
    public function getOwnedByUserButUsedByOthers(User $user): Collection
    {
        return Artist::query()
            ->where('user_id', $user->id)
            ->where(static function (Builder $query) use ($user): void {
                $query->whereHas('songs', static fn (Builder $songs) => $songs->where(
                    'owner_id',
                    '<>',
                    $user->id,
                ))->orWhereHas('albums.songs', static fn (Builder $songs) => $songs->where(
                    'owner_id',
                    '<>',
                    $user->id,
                ));
            })
            ->get();
    }

    public function getMany(array $ids, bool $preserveOrder = false, ?User $user = null): Collection
    {
        $artists = Artist::query()
            ->onlyStandard()
            ->withUserContext(user: $user ?? $this->auth->user())
            ->getWhereInChunks('artists.id', $ids);

        return $preserveOrder ? $artists->orderByArray($ids) : $artists;
    }

    /** @return Collection<int, Artist> */
    public function getFavorites(?int $limit = null, int $offset = 0, ?User $user = null): Collection
    {
        return Artist::query()
            ->onlyStandard()
            ->withUserContext(user: $user ?? $this->auth->user(), favoritesOnly: true)
            ->orderBy('favorites.position')
            ->when($offset > 0, static fn (Builder $query) => $query->offset($offset))
            ->when($limit !== null, static fn (Builder $query) => $query->limit($limit))
            ->get();
    }

    /** @return LazyCollection<array-key, Artist> */
    public function lazyGetWithoutMbid(): LazyCollection
    {
        return Artist::query()->onlyStandard()->whereNull('mbid')->lazyById();
    }

    public function countWithoutMbid(): int
    {
        return Artist::query()->onlyStandard()->whereNull('mbid')->count();
    }

    public function countAccessibleByUser(User $user): int
    {
        return Artist::query()
            ->onlyStandard()
            ->onlyAlbumArtists()
            ->setScopedUser($user)
            ->accessible()
            ->count();
    }

    public function getAll(?User $user = null): Collection
    {
        return Artist::query()
            ->withUserContext(user: $user ?? $this->auth->user(), includeAlbumCover: false)
            ->withAlbumCoverPresence()
            ->onlyStandard()
            ->onlyAlbumArtists()
            ->withAlbumCountIncludingAppearances()
            ->orderBy('name')
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
            Artist::query()
                ->withUserContext(user: $user ?? $this->auth->user(), favoritesOnly: $favoritesOnly)
                ->onlyStandard()
                ->onlyAlbumArtists()
                ->sort($sortColumn, $sortDirection),
            idColumn: 'artists.id',
            perPage: 60,
        );
    }

    /** @return Collection<int, Artist> */
    public function getOrdered(
        string $sortColumn,
        string $sortDirection,
        int $limit,
        int $offset = 0,
        ?User $user = null,
    ): Collection {
        return Artist::query()
            ->onlyStandard()
            ->onlyAlbumArtists()
            ->withUserContext(user: $user ?? $this->auth->user())
            ->sort($sortColumn, $sortDirection)
            ->orderBy('artists.id')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function search(string $keywords, int $limit, ?User $user = null): Collection
    {
        return $this->getMany(
            ids: Artist::search($keywords)->take(self::SEARCH_CANDIDATE_LIMIT)->keys()->all(),
            preserveOrder: true,
            user: $user,
        )->take($limit);
    }
}
