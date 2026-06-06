<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Http\Responses\Subsonic\Resources\AlbumResource;
use App\Http\Responses\Subsonic\Resources\ArtistResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\Album;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class GetArtistController extends Controller
{
    public function __construct(
        private readonly ArtistRepository $artistRepository,
        private readonly AlbumRepository $albumRepository,
    ) {}

    /** @param User $user */
    public function __invoke(IdRequest $request, Authenticatable $user)
    {
        $artist = $this->artistRepository->getOne($request->id);
        $artist->loadCount('albums');

        $albums = $this->albumRepository->getByArtist($artist)->loadCount('songs')->loadSum('songs', 'length');

        return SubsonicResponse::ok([
            'artist' => ArtistResource::toArray($artist, $user)
                + [
                    'album' => $albums->map(static fn (Album $album) => AlbumResource::toArray($album, $user))->all(),
                ],
        ]);
    }
}
