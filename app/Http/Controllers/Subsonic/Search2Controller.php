<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\Search2Request;
use App\Http\Responses\Subsonic\Resources\AlbumChildResource;
use App\Http\Responses\Subsonic\Resources\ArtistResource;
use App\Http\Responses\Subsonic\Resources\SongResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class Search2Controller extends Controller
{
    public function __construct(
        private readonly ArtistRepository $artistRepository,
        private readonly AlbumRepository $albumRepository,
        private readonly SongRepository $songRepository,
    ) {}

    /** @param User $user */
    public function __invoke(Search2Request $request, Authenticatable $user)
    {
        $query = (string) $request->input('query');

        if ($query === '') {
            return SubsonicResponse::ok(['searchResult2' => ['artist' => [], 'album' => [], 'song' => []]]);
        }

        $artists = $this->artistRepository->search($query, $request->integer('artistCount', 20))->loadCount('albums');
        $albums = $this->albumRepository->search($query, $request->integer('albumCount', 20));
        $songs = $this->songRepository->search($query, $request->integer('songCount', 20));

        return SubsonicResponse::ok([
            'searchResult2' => [
                'artist' => $artists->map(static fn (Artist $artist) => ArtistResource::toArray($artist, $user))->all(),
                'album' => $albums->map(AlbumChildResource::toArray(...))->all(),
                'song' => $songs->map(static fn (Song $song) => SongResource::toArray($song, $user))->all(),
            ],
        ]);
    }
}
