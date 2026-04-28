<?php

namespace App\Repositories;

use App\Models\Podcast;
use App\Models\User;
use App\Repositories\Contracts\ScoutableRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Repository<Podcast>
 * @implements ScoutableRepository<Podcast>
 */
class PodcastRepository extends Repository implements ScoutableRepository
{
    public function findOneByUrl(string $url): ?Podcast
    {
        return $this->findOneBy(['url' => $url]);
    }

    public function getAllSubscribedByUser(bool $favoritesOnly, ?User $user = null): Collection
    {
        $user ??= $this->auth->user();

        return Podcast::query()
            ->with(['subscribers' => static fn ($query) => $query->where('users.id', $user->id)])
            ->setScopedUser($user)
            ->withFavoriteStatus(favoritesOnly: $favoritesOnly)
            ->subscribed()
            ->get();
    }

    public function getMany(array $ids, bool $preserveOrder = false, ?User $user = null): Collection
    {
        $user ??= $this->auth->user();

        $podcasts = Podcast::query()
            ->with(['subscribers' => static fn ($query) => $query->where('users.id', $user->id)])
            ->setScopedUser($user)
            ->subscribed()
            ->whereIn('podcasts.id', $ids)
            ->distinct()
            ->get();

        return $preserveOrder ? $podcasts->orderByArray($ids) : $podcasts;
    }

    public function search(string $keywords, int $limit, ?User $user = null): Collection
    {
        return $this->getMany(
            ids: Podcast::search($keywords)
                ->take($limit)
                ->get()
                ->modelKeys(),
            preserveOrder: true,
            user: $user,
        );
    }
}
