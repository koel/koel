<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\Search3Request;
use App\Http\Responses\Subsonic\Resources\AlbumResource;
use App\Http\Responses\Subsonic\Resources\ArtistResource;
use App\Http\Responses\Subsonic\Resources\SongResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class Search3Controller extends Controller
{
    public function __construct(
        private readonly ArtistRepository $artistRepository,
        private readonly AlbumRepository $albumRepository,
        private readonly SongRepository $songRepository,
    ) {}

    /** @param User $user */
    public function __invoke(Search3Request $request, Authenticatable $user)
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
                'artist' => $artists->map(static fn (Artist $artist) => ArtistResource::toArray($artist, $user))->all(),
                'album' => $albums->map(static fn (Album $album) => AlbumResource::toArray($album, $user))->all(),
                'song' => $songs->map(static fn (Song $song) => SongResource::toArray($song, $user))->all(),
            ],
        ]);
    }
}
