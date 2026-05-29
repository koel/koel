<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\Search2Request;
use App\Http\Responses\Subsonic\Resources\SearchResult2Resource;
use App\Http\Responses\Subsonic\SubsonicResponse;
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
            return SubsonicResponse::ok(['searchResult2' => SearchResult2Resource::empty()]);
        }

        $artists = $this->artistRepository->search($query, $request->integer('artistCount', 20))->loadCount('albums');
        $albums = $this->albumRepository->search($query, $request->integer('albumCount', 20));
        $songs = $this->songRepository->search($query, $request->integer('songCount', 20));

        return SubsonicResponse::ok([
            'searchResult2' => SearchResult2Resource::toArray($artists, $albums, $songs, $user),
        ]);
    }
}
