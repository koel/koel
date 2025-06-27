<?php

namespace App\Repositories;

use App\Models\Podcast;
use App\Models\User;
use App\Repositories\Contracts\ScoutableRepository;
use Illuminate\Database\Eloquent\Collection;

/** @extends Repository<Podcast> */
class PodcastRepository extends Repository implements ScoutableRepository
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
            ->distinct()
            ->get('podcasts.*');

        return $preserveOrder ? $podcasts->orderByArray($ids) : $podcasts;
    }

    /** @return Collection<Podcast>|array<array-key, Podcast> */
    public function search(string $keywords, int $limit, ?User $scopedUser = null): Collection
    {
        return $this->getMany(
            ids: Podcast::search($keywords)->get()->take($limit)->modelKeys(),
            preserveOrder: true,
        );
    }
}
