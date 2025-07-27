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

    /** @return Collection<Podcast>|array<array-key, Podcast> */
    public function getAllSubscribedByUser(bool $favoritesOnly, ?User $user = null): Collection
    {
        return Podcast::query()
            ->with('subscribers')
            ->setScopedUser($user ?? $this->auth->user())
            ->withFavoriteStatus(favoritesOnly: $favoritesOnly)
            ->subscribed()
            ->get();
    }

    /** @return Collection<Podcast>|array<array-key, Podcast> */
    public function getMany(array $ids, bool $preserveOrder = false, ?User $user = null): Collection
    {
        $podcasts = Podcast::query()
            ->with('subscribers')
            ->setScopedUser($user ?? $this->auth->user())
            ->subscribed()
            ->whereIn('podcasts.id', $ids)
            ->distinct()
            ->get();

        return $preserveOrder ? $podcasts->orderByArray($ids) : $podcasts;
    }

    /** @return Collection<Podcast>|array<array-key, Podcast> */
    public function search(string $keywords, int $limit, ?User $user = null): Collection
    {
        return $this->getMany(
            ids: Podcast::search($keywords)->get()->take($limit)->modelKeys(),
            preserveOrder: true,
            user: $user,
        );
    }
}
