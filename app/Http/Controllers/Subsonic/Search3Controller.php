<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\Search3Request;
use App\Http\Responses\Subsonic\Resources\SearchResultResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Services\Subsonic\SubsonicSearchService;
use Illuminate\Contracts\Auth\Authenticatable;

class Search3Controller extends Controller
{
    public function __construct(
        private readonly SubsonicSearchService $searchService,
    ) {}

    /** @param User $user */
    public function __invoke(Search3Request $request, Authenticatable $user)
    {
        $query = (string) $request->input('query');

        $artists = $this->searchService
            ->searchArtists($query, $request->integer('artistCount', 20), $request->integer('artistOffset'), $user)
            ->loadCount('albums');

        $albums = $this->searchService
            ->searchAlbums($query, $request->integer('albumCount', 20), $request->integer('albumOffset'), $user)
            ->loadCount('songs')
            ->loadSum('songs', 'length');

        $songs = $this->searchService->searchSongs(
            $query,
            $request->integer('songCount', 20),
            $request->integer('songOffset'),
            $user,
        );

        return SubsonicResponse::ok([
            'searchResult3' => SearchResultResource::toArray($artists, $albums, $songs, $user),
        ]);
    }
}
