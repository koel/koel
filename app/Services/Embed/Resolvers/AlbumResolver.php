<?php

namespace App\Services\Embed\Resolvers;

use App\Models\Album;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\SongRepository;
use App\Services\Embed\Resolvers\Contracts\Resolver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/** @implements Resolver<Album> */
class AlbumResolver implements Resolver
{
    public function __construct(
        private readonly AlbumRepository $albumRepository,
        private readonly SongRepository $songRepository,
    ) {}

    public function findEmbeddable(?string $embeddableId, User $embedOwner): ?Album
    {
        return $this->albumRepository->findOneBy(['id' => $embeddableId]);
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
}
