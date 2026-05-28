<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\FavoriteRequest;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\SongRepository;
use App\Services\FavoriteService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class StarController extends Controller
{
    public function __construct(
        private readonly SongRepository $songRepository,
        private readonly AlbumRepository $albumRepository,
        private readonly ArtistRepository $artistRepository,
        private readonly FavoriteService $favoriteService,
    ) {}

    /** @param User $user */
    public function __invoke(FavoriteRequest $request, Authenticatable $user)
    {
        $targets = $this->collectTargets($request, $user);

        if ($targets->isNotEmpty()) {
            $this->favoriteService->batchFavorite($targets, $user);
        }

        return SubsonicResponse::ok();
    }

    /** @return Collection<int, Model> */
    private function collectTargets(FavoriteRequest $request, User $user): Collection
    {
        $targets = new Collection();

        foreach ($this->songRepository->getMany($request->id, scopedUser: $user) as $song) {
            $targets->push($song);
        }

        foreach ($this->albumRepository->getMany($request->albumId, user: $user) as $album) {
            $targets->push($album);
        }

        foreach ($this->artistRepository->getMany($request->artistId, user: $user) as $artist) {
            $targets->push($artist);
        }

        return $targets;
    }
}
