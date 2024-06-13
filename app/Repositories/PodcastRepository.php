<?php

namespace App\Repositories;

use App\Models\Podcast;
use App\Models\User;
use Illuminate\Support\Collection;

/** @extends Repository<Podcast> */
class PodcastRepository extends Repository
{
    public function findOneByUrl(string $url): ?Podcast
    {
        return $this->findOneBy(['url' => $url]);
    }

    /** @return Collection<Podcast> */
    public function getAllByUser(User $user): Collection
    {
        return $user->podcasts()->orderByPivot('updated_at', 'desc')->get();
    }

    /** @return Collection<Podcast> */
    public function getMany(array $ids, bool $preserveOrder = false, ?User $user = null): Collection
    {
        $podcasts = Podcast::query()
            ->subscribedBy($user ?? $this->auth->user())
            ->whereIn('podcasts.id', $ids)
            ->groupBy('podcasts.id')
            ->distinct()
            ->get('podcasts.*');

        return $preserveOrder ? $podcasts->orderByArray($ids) : $podcasts;
    }
}
