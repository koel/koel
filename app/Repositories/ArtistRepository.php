<?php

namespace App\Repositories;

use App\Models\Artist;
use App\Models\User;
use App\Repositories\Contracts\ScoutableRepository;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Repository<Artist>
 * @implements ScoutableRepository<Artist>
 */
class ArtistRepository extends Repository implements ScoutableRepository
{
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
        return Artist::query()
            ->onlyStandard()
            ->onlyAlbumArtists()
            ->withUserContext(user: $user ?? $this->auth->user())
            ->latest()
            ->limit($count)
            ->get();
    }

    public function getMostPlayed(int $count = 6, ?User $user = null): Collection
    {
        return Artist::query()
            ->withUserContext(user: $user ?? $this->auth->user(), includePlayCount: true)
            ->onlyStandard()
            ->onlyAlbumArtists()
            ->orderByDesc('play_count')
            ->limit($count)
            ->get();
    }

    public function getMany(array $ids, bool $preserveOrder = false, ?User $user = null): Collection
    {
        $artists = Artist::query()
            ->onlyStandard()
            ->withUserContext(user: $user ?? $this->auth->user())
            ->whereIn('artists.id', $ids)
            ->get();

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

    public function getAll(?User $user = null): Collection
    {
        return Artist::query()
            ->withUserContext(user: $user ?? $this->auth->user())
            ->onlyStandard()
            ->onlyAlbumArtists()
            ->withCount('albums')
            ->orderBy('name')
            ->get();
    }

    public function getForListing(
        string $sortColumn,
        string $sortDirection,
        bool $favoritesOnly = false,
        ?User $user = null,
    ): Paginator {
        return Artist::query()
            ->withUserContext(user: $user ?? $this->auth->user(), favoritesOnly: $favoritesOnly)
            ->onlyStandard()
            ->onlyAlbumArtists()
            ->sort($sortColumn, $sortDirection)
            ->simplePaginate(21);
    }

    public function search(string $keywords, int $limit, ?User $user = null): Collection
    {
        return $this->getMany(
            ids: Artist::search($keywords)->take($limit)->get()->modelKeys(),
            preserveOrder: true,
            user: $user,
        );
    }
}
