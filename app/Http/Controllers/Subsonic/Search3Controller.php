<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\Search3Request;
use App\Http\Responses\Subsonic\Resources\AlbumResource;
use App\Http\Responses\Subsonic\Resources\ArtistResource;
use App\Http\Responses\Subsonic\Resources\SongResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\SongRepository;

class Search3Controller extends Controller
{
    public function __construct(
        private readonly ArtistRepository $artistRepository,
        private readonly AlbumRepository $albumRepository,
        private readonly SongRepository $songRepository,
    ) {}

    public function __invoke(Search3Request $request)
    {
        $artists = $this->artistRepository
            ->search($request->input('query'), $request->integer('artistCount', 20))
            ->loadCount('albums');

        $albums = $this->albumRepository
            ->search($request->input('query'), $request->integer('albumCount', 20))
            ->loadCount('songs')
            ->loadSum('songs', 'length');

        $songs = $this->songRepository->search($request->input('query'), $request->integer('songCount', 20));

        return SubsonicResponse::ok([
            'searchResult3' => [
                'artist' => $artists->map(ArtistResource::toArray(...))->all(),
                'album' => $albums->map(AlbumResource::toArray(...))->all(),
                'song' => $songs->map(SongResource::toArray(...))->all(),
            ],
        ]);
    }
}
