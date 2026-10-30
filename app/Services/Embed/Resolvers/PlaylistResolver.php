<?php

namespace App\Services\Embed\Resolvers;

use App\Models\Playlist;
use App\Models\User;
use App\Repositories\PlaylistRepository;
use App\Repositories\SongRepository;
use App\Services\Embed\Resolvers\Contracts\Resolver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/** @implements Resolver<Playlist> */
class PlaylistResolver implements Resolver
{
    public function __construct(
        private readonly PlaylistRepository $playlistRepository,
        private readonly SongRepository $songRepository,
    ) {}

    public function findEmbeddable(?string $embeddableId, User $embedOwner): ?Playlist
    {
        return $this->playlistRepository->findOneBy(['id' => $embeddableId]);
    }

    /** @param Playlist $embeddable */
    public function getPlayables(Model $embeddable, User $embedOwner): Collection
    {
        return $this->songRepository->getByPlaylist($embeddable, $embedOwner);
    }

    /** @param Playlist $embeddable */
    public function getEmbeddableId(Model $embeddable): string
    {
        return $embeddable->id;
    }
}
