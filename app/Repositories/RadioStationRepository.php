<?php

namespace App\Repositories;

use App\Models\RadioStation;
use App\Models\User;
use App\Repositories\Contracts\ScoutableRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Repository<RadioStation>
 * @implements ScoutableRepository<RadioStation>
 */
class RadioStationRepository extends Repository implements ScoutableRepository
{
    public function getMany(array $ids, bool $preserveOrder = false, ?User $user = null): Collection
    {
        $stations = RadioStation::query()
            ->withUserContext(user: $user ?? $this->auth->user())
            ->getWhereInChunks('radio_stations.id', $ids);

        return $preserveOrder ? $stations->orderByArray($ids) : $stations;
    }

    public function search(string $keywords, int $limit, ?User $user = null): Collection
    {
        return $this->getMany(
            ids: RadioStation::search($keywords)->take(self::SEARCH_CANDIDATE_LIMIT)->keys()->all(),
            preserveOrder: true,
            user: $user,
        )->take($limit);
    }

    /** @return Collection<int, RadioStation> */
    public function getAllForUser(User $user): Collection
    {
        return RadioStation::query()->withUserContext(user: $user)->get();
    }

    public function countAccessibleByUser(User $user): int
    {
        return RadioStation::query()->withUserContext(user: $user, includeFavoriteStatus: false)->count();
    }

    /** @return Collection<int, RadioStation> */
    public function getRecentlyAddedForUser(int $count, User $user): Collection
    {
        return RadioStation::query()
            ->withUserContext(user: $user)
            ->orderByDesc('radio_stations.created_at')
            ->limit($count)
            ->get();
    }

    public function findOneWithUserContext(string $id, User $user, bool $withFavoriteStatus = true): RadioStation
    {
        return RadioStation::query()
            ->withUserContext(user: $user, includeFavoriteStatus: $withFavoriteStatus)
            ->findOrFail($id);
    }
}
