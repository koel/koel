<?php

namespace App\Services\Embed\Resolvers;

use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\Embed\Resolvers\Contracts\Resolver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;

/** @implements Resolver<User> */
class FavoritesResolver implements Resolver
{
    public function __construct(
        private readonly SongRepository $songRepository,
    ) {}

    public function findEmbeddable(?string $embeddableId, User $embedOwner): User
    {
        return $embedOwner;
    }

    /** @param User $embeddable */
    public function canEmbed(User $user, Model $embeddable): bool
    {
        return !$user->isGuest() && $user->is($embeddable);
    }

    /** @param User $embeddable */
    public function getPlayables(Model $embeddable, User $embedOwner): Collection
    {
        return $this->songRepository->getFavorites(scopedUser: $embeddable);
    }

    /** @param User $embeddable */
    public function getEmbeddableId(Model $embeddable): null
    {
        return null;
    }

    /** @param User $embeddable */
    public function makeEmbeddableResource(Model $embeddable): JsonResource
    {
        return JsonResource::make([
            'type' => 'favorites',
            'owner_name' => $embeddable->name,
        ]);
    }
}
