<?php

namespace App\Repositories;

use App\Models\Artist;
use App\Models\User;
use App\Repositories\Contracts\ScoutableRepository;
use Illuminate\Contracts\Pagination\Paginator;
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

    /** @return Collection|array<array-key, Artist> */
    public function getMostPlayed(int $count = 6, ?User $user = null): Collection
    {
        return Artist::query()
            ->withUserContext(user: $user ?? $this->auth->user(), includePlayCount: true)
            ->onlyStandard()
            ->orderByDesc('play_count')
            ->limit($count)
            ->get();
    }

    /** @return Collection|array<array-key, Artist> */
    public function getMany(array $ids, bool $preserveOrder = false, ?User $user = null): Collection
    {
        $artists = Artist::query()
            ->onlyStandard()
            ->withUserContext(user: $user ?? $this->auth->user())
            ->whereIn('artists.id', $ids)
            ->get();

        return $preserveOrder ? $artists->orderByArray($ids) : $artists;
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
            ->sort($sortColumn, $sortDirection)
            ->simplePaginate(21);
    }

    /** @return Collection<Artist>|array<array-key, Artist> */
    public function search(string $keywords, int $limit, ?User $user = null): Collection
    {
        return $this->getMany(
            ids: Artist::search($keywords)->get()->take($limit)->modelKeys(),
            preserveOrder: true,
            user: $user,
        );
    }
}
