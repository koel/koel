<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Http\Responses\Subsonic\Resources\AlbumResource;
use App\Http\Responses\Subsonic\Resources\ArtistResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;

class GetArtistController extends Controller
{
    public function __construct(
        private readonly ArtistRepository $artistRepository,
        private readonly AlbumRepository $albumRepository,
    ) {}

    public function __invoke(IdRequest $request)
    {
        $artist = $this->artistRepository->getOne($request->id);
        $artist->loadCount('albums');

        $albums = $this->albumRepository->getByArtist($artist)->loadCount('songs')->loadSum('songs', 'length');
        $albumPayloads = [];

        foreach ($albums as $album) {
            $albumPayloads[] = AlbumResource::toArray($album);
        }

        return SubsonicResponse::ok([
            'artist' => ArtistResource::toArray($artist) + ['album' => $albumPayloads],
        ]);
    }
}
