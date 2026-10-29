<?php

namespace App\Services\Embeddables;

use App\Http\Resources\ArtistResource;
use App\Models\Artist;
use App\Models\User;
use App\Repositories\ArtistRepository;
use App\Repositories\SongRepository;
use App\Services\Embeddables\Contracts\EmbeddableResolver;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;

/** @implements EmbeddableResolver<Artist> */
class ArtistEmbeddableResolver implements EmbeddableResolver
{
    public function __construct(
        private readonly ArtistRepository $artistRepository,
        private readonly SongRepository $songRepository,
        private readonly Gate $gate,
    ) {}

    public function findEmbeddable(?string $embeddableId, User $embedOwner): ?Artist
    {
        return $this->artistRepository->findOneBy(['id' => $embeddableId]);
    }

    /** @param Artist $embeddable */
    public function canEmbed(User $user, Model $embeddable): bool
    {
        return $this->gate->forUser($user)->allows('embed', $embeddable);
    }

    /** @param Artist $embeddable */
    public function getPlayables(Model $embeddable, User $embedOwner): Collection
    {
        return $this->songRepository->getByArtist($embeddable, $embedOwner);
    }

    /** @param Artist $embeddable */
    public function getEmbeddableId(Model $embeddable): string
    {
        return $embeddable->id;
    }

    /** @param Artist $embeddable */
    public function makeEmbeddableResource(Model $embeddable): JsonResource
    {
        return ArtistResource::make($embeddable);
    }
}
