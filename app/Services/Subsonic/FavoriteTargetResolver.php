<?php

namespace App\Services\Subsonic;

use App\Http\Requests\Subsonic\FavoriteRequest;
use App\Models\Contracts\Favoriteable;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\SongRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class FavoriteTargetResolver
{
    public function __construct(
        private readonly SongRepository $songRepository,
        private readonly AlbumRepository $albumRepository,
        private readonly ArtistRepository $artistRepository,
    ) {}

    /**
     * Resolve all id/albumId/artistId params to a Collection typed for FavoriteService::batchFavorite.
     *
     * @return Collection<int, Model>
     */
    public function resolveForFavorite(FavoriteRequest $request, User $user): Collection
    {
        return $this->collect($request, $user);
    }

    /**
     * Same resolution as resolveForFavorite, narrower return for batchUndoFavorite.
     *
     * @return Collection<int, Model&Favoriteable>
     */
    public function resolveForUnfavorite(FavoriteRequest $request, User $user): Collection
    {
        return $this->collect($request, $user);
    }

    private function collect(FavoriteRequest $request, User $user): Collection
    {
        $targets = new Collection();

        if ($request->id) {
            foreach ($this->songRepository->getMany($request->id, preserveOrder: true, scopedUser: $user) as $song) {
                $targets->push($song);
            }
        }

        if ($request->albumId) {
            foreach ($this->albumRepository->getMany($request->albumId, preserveOrder: true, user: $user) as $album) {
                $targets->push($album);
            }
        }

        if ($request->artistId) {
            foreach ($this->artistRepository->getMany(
                $request->artistId,
                preserveOrder: true,
                user: $user,
            ) as $artist) {
                $targets->push($artist);
            }
        }

        return $targets;
    }
}
