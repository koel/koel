<?php

namespace App\Services\Embeddables;

use App\Http\Resources\AlbumResource;
use App\Models\Album;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\SongRepository;
use App\Services\Embeddables\Contracts\EmbeddableResolver;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;

/** @implements EmbeddableResolver<Album> */
class AlbumEmbeddableResolver implements EmbeddableResolver
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
        private readonly SongRepository $songRepository,
        private readonly Gate $gate,
    ) {}

    public function findEmbeddable(?string $embeddableId, User $embedOwner): ?Album
    {
        return $this->albumRepository->findOneBy(['id' => $embeddableId]);
    }

    /** @param Album $embeddable */
    public function canEmbed(User $user, Model $embeddable): bool
    {
        return $this->gate->forUser($user)->allows('embed', $embeddable);
    }

    /** @param Album $embeddable */
    public function getPlayables(Model $embeddable, User $embedOwner): Collection
    {
        return $this->songRepository->getByAlbum($embeddable, $embedOwner);
    }

    /** @param Album $embeddable */
    public function getEmbeddableId(Model $embeddable): string
    {
        return $embeddable->id;
    }

    /** @param Album $embeddable */
    public function makeEmbeddableResource(Model $embeddable): JsonResource
    {
        return AlbumResource::make($embeddable);
    }
}
