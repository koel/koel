<?php

namespace App\Services\Embed\Resolvers;

use App\Models\Artist;
use App\Models\User;
use App\Repositories\ArtistRepository;
use App\Repositories\SongRepository;
use App\Services\Embed\Resolvers\Contracts\Resolver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/** @implements Resolver<Artist> */
class ArtistResolver implements Resolver
{
    public function __construct(
        private readonly ArtistRepository $artistRepository,
        private readonly SongRepository $songRepository,
    ) {}

    public function findEmbeddable(?string $embeddableId, User $embedOwner): ?Artist
    {
        return $this->artistRepository->findOneBy(['id' => $embeddableId]);
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
}
