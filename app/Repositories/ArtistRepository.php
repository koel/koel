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
    /** @return Collection|array<array-key, Artist> */
    public function getMostPlayed(int $count = 6, ?User $user = null): Collection
    {
        $user ??= auth()->user();

        return Artist::query()
            ->isStandard()
            ->accessibleBy($user)
            ->withPlayCount($user)
            ->addSelect('artists.*')
            ->orderByDesc('play_count')
            ->limit($count)
            ->get();
    }

    /** @return Collection|array<array-key, Artist> */
    public function getMany(array $ids, bool $preserveOrder = false, ?User $user = null): Collection
    {
        $artists = Artist::query()
            ->isStandard()
            ->accessibleBy($user ?? auth()->user())
            ->whereIn('artists.id', $ids)
            ->distinct()
            ->get('artists.*');

        return $preserveOrder ? $artists->orderByArray($ids) : $artists;
    }

    public function getForListing(string $sortColumn, string $sortDirection, ?User $user = null): Paginator
    {
        return Artist::query()
            ->isStandard()
            ->accessibleBy($user ?? auth()->user())
            ->sort($sortColumn, $sortDirection)
            ->distinct()
            ->orderBy('artists.name')
            ->select('artists.*')
            ->simplePaginate(21);
    }

    /** @return Collection<Artist>|array<array-key, Artist> */
    public function search(string $keywords, int $limit, ?User $scopedUser = null): Collection
    {
        return $this->getMany(
            ids: Artist::search($keywords)->get()->take($limit)->modelKeys(),
            preserveOrder: true,
            user: $scopedUser,
        );
    }
}
