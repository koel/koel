<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Responses\Subsonic\Resources\StarredResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class GetStarredController extends Controller
{
    public function __construct(
        private readonly ArtistRepository $artistRepository,
        private readonly AlbumRepository $albumRepository,
        private readonly SongRepository $songRepository,
    ) {}

    /** @param User $user */
    public function __invoke(Authenticatable $user)
    {
        $artists = $this->artistRepository->getFavorites(user: $user)->loadCount('albums');
        $albums = $this->albumRepository->getFavorites(user: $user)->loadCount('songs')->loadSum('songs', 'length');
        $songs = $this->songRepository->getFavorites(scopedUser: $user);

        return SubsonicResponse::ok([
            'starred' => StarredResource::toArray($artists, $albums, $songs, $user),
        ]);
    }
}
