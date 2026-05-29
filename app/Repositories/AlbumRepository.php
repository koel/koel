<?php

namespace App\Repositories;

use App\Models\Album;
use App\Models\Artist;
use App\Models\User;
use App\Repositories\Contracts\ScoutableRepository;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

/**
 * @extends Repository<Album>
 * @implements ScoutableRepository<Album>
 */
// @mago-ignore lint:too-many-methods,cyclomatic-complexity
class AlbumRepository extends Repository implements ScoutableRepository
{
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

    public function getRecentlyAdded(int $count = 6, ?User $user = null): Collection
    {
        return Album::query()
            ->onlyStandard()
            ->withUserContext(user: $user ?? $this->auth->user())
            ->latest()
            ->limit($count)
            ->get();
    }

    public function getMostPlayed(int $count = 6, ?User $user = null): Collection
    {
        return Album::query()
            ->onlyStandard()
            ->withUserContext(user: $user ?? $this->auth->user(), includePlayCount: true)
            ->orderByDesc('play_count')
            ->limit($count)
            ->get();
    }

    public function getRecentlyPlayed(int $count = 6, ?User $user = null): Collection
    {
        return Album::query()
            ->onlyStandard()
            ->withUserContext(user: $user ?? $this->auth->user(), includePlayCount: true)
            ->addSelect(DB::raw('MAX(interactions.last_played_at) as last_played_at'))
            ->havingRaw('MAX(interactions.last_played_at) IS NOT NULL')
            ->orderByDesc('last_played_at')
            ->limit($count)
            ->get();
    }

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
            ->whereHas('songs.genres', static fn (Builder $query) => $query->where('genres.name', $genreName))
            ->orderBy('albums.name')
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
            ->offset($offset)
            ->limit($size)
            ->get();
    }

    public function getMany(array $ids, bool $preserveOrder = false, ?User $user = null): Collection
    {
        $albums = Album::query()
            ->onlyStandard()
            ->withUserContext(user: $user ?? $this->auth->user())
            ->whereIn('albums.id', $ids)
            ->get();

        return $preserveOrder ? $albums->orderByArray($ids) : $albums;
    }

    /** @return Collection<int, Album> */
    public function getByArtist(Artist $artist, ?User $user = null): Collection
    {
        return Album::query()
            ->withUserContext(user: $user ?? $this->auth->user())
            ->where(static function (Builder $query) use ($artist): void {
                $query->whereBelongsTo($artist)->orWhereHas('songs', static function (Builder $songQuery) use (
                    $artist,
                ): void {
                    $songQuery->whereBelongsTo($artist);
                });
            })
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
        return Album::query()
            ->onlyStandard()
            ->withUserContext(user: $user ?? $this->auth->user())
            ->inRandomOrder()
            ->limit($limit)
            ->get();
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
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function getForListing(
        string $sortColumn,
        string $sortDirection,
        bool $favoritesOnly = false,
        ?User $user = null,
    ): Paginator {
        return Album::query()
            ->onlyStandard()
            ->withUserContext(user: $user ?? $this->auth->user(), favoritesOnly: $favoritesOnly)
            ->sort($sortColumn, $sortDirection)
            ->simplePaginate(21);
    }

    public function search(string $keywords, int $limit, ?User $user = null): Collection
    {
        return $this->getMany(
            ids: Album::search($keywords)->take($limit)->get()->modelKeys(),
            preserveOrder: true,
            user: $user,
        );
    }
}
