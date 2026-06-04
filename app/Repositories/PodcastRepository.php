<?php

namespace App\Repositories;

use App\Models\Podcast;
use App\Models\User;
use App\Repositories\Contracts\ScoutableRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

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

    /** @param string $id */
    public function getOne($id, ?User $user = null): Podcast
    {
        $user ??= $this->auth->user();

        return Podcast::query()
            ->with(['subscribers' => static fn (Relation $query) => $query->where('users.id', $user->id)])
            ->setScopedUser($user)
            ->withUserContext($user)
            ->findOrFail($id);
    }

    /** @return Collection<int, Podcast> */
    public function getAllSubscribedByUser(bool $favoritesOnly, ?User $user = null): Collection
    {
        $user ??= $this->auth->user();

        return Podcast::query()
            ->with(['subscribers' => static fn (Relation $query) => $query->where('users.id', $user->id)])
            ->setScopedUser($user)
            ->withUserContext($user, favoritesOnly: $favoritesOnly)
            ->subscribed()
            ->get();
    }

    /** @return Collection<int, Podcast> */
    public function getMany(array $ids, bool $preserveOrder = false, ?User $user = null): Collection
    {
        $user ??= $this->auth->user();

        $podcasts = Podcast::query()
            ->with(['subscribers' => static fn (Relation $query) => $query->where('users.id', $user->id)])
            ->setScopedUser($user)
            ->withUserContext($user)
            ->subscribed()
            ->whereIn('podcasts.id', $ids)
            ->distinct()
            ->get();

        return $preserveOrder ? $podcasts->orderByArray($ids) : $podcasts;
    }

    public function search(string $keywords, int $limit, ?User $user = null): Collection
    {
        return $this->getMany(
            ids: Podcast::search($keywords)->take($limit)->get()->modelKeys(),
            preserveOrder: true,
            user: $user,
        );
    }
}
