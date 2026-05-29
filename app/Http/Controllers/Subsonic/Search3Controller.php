<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\Search3Request;
use App\Http\Responses\Subsonic\Resources\SearchResultResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
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
        $query = (string) $request->input('query');

        if ($query === '') {
            return SubsonicResponse::ok(['searchResult3' => SearchResultResource::empty()]);
        }

        $artists = $this->artistRepository->search($query, $request->integer('artistCount', 20))->loadCount('albums');

        $albums = $this->albumRepository
            ->search($query, $request->integer('albumCount', 20))
            ->loadCount('songs')
            ->loadSum('songs', 'length');

        $songs = $this->songRepository->search($query, $request->integer('songCount', 20));

        return SubsonicResponse::ok([
            'searchResult3' => SearchResultResource::toArray($artists, $albums, $songs, $user),
        ]);
    }
}
