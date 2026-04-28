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
            ->whereIn('radio_stations.id', $ids)
            ->get();

        return $preserveOrder ? $stations->orderByArray($ids) : $stations;
    }

    public function search(string $keywords, int $limit, ?User $user = null): Collection
    {
        return $this->getMany(
            ids: RadioStation::search($keywords)
                ->take($limit)
                ->get()
                ->modelKeys(),
            preserveOrder: true,
            user: $user,
        );
    }

    public function getAllForUser(User $user): Collection
    {
        return RadioStation::query()->withUserContext(user: $user)->get();
    }

    public function findOneWithUserContext(string $id, User $user, bool $withFavoriteStatus = true): RadioStation
    {
        return RadioStation::query()
            ->withUserContext(user: $user, includeFavoriteStatus: $withFavoriteStatus)
            ->findOrFail($id);
    }
}
