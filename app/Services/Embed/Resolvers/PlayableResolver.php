<?php

namespace App\Services\Embed\Resolvers;

use App\Http\Resources\SongResource;
use App\Models\Song;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\Embed\Resolvers\Contracts\Resolver;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;

/** @implements Resolver<Song> */
class PlayableResolver implements Resolver
{
    public function __construct(
        private readonly SongRepository $songRepository,
        private readonly Gate $gate,
    ) {}

    public function findEmbeddable(?string $embeddableId, User $embedOwner): ?Song
    {
        return $this->songRepository->findOneBy(['id' => $embeddableId]);
    }

    /** @param Song $embeddable */
    public function canEmbed(User $user, Model $embeddable): bool
    {
        return $this->gate->forUser($user)->allows('embed', $embeddable);
    }

    /** @param Song $embeddable */
    public function getPlayables(Model $embeddable, User $embedOwner): Collection
    {
        return $this->songRepository->getMany(ids: [$embeddable->id], scopedUser: $embedOwner);
    }

    /** @param Song $embeddable */
    public function getEmbeddableId(Model $embeddable): string
    {
        return $embeddable->id;
    }

    /** @param Song $embeddable */
    public function makeEmbeddableResource(Model $embeddable): JsonResource
    {
        return SongResource::make($embeddable);
    }
}
