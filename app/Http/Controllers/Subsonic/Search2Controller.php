<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\Search2Request;
use App\Http\Responses\Subsonic\Resources\SearchResult2Resource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Services\Subsonic\SubsonicSearchService;
use Illuminate\Contracts\Auth\Authenticatable;

class Search2Controller extends Controller
{
    public function __construct(
        private readonly SubsonicSearchService $searchService,
    ) {}

    /** @param User $user */
    public function __invoke(Search2Request $request, Authenticatable $user)
    {
        $query = (string) $request->input('query');

        $artists = $this->searchService
            ->searchArtists($query, $request->integer('artistCount', 20), $request->integer('artistOffset'), $user)
            ->loadCount('albums');

        $albums = $this->searchService->searchAlbums(
            $query,
            $request->integer('albumCount', 20),
            $request->integer('albumOffset'),
            $user,
        );

        $songs = $this->searchService->searchSongs(
            $query,
            $request->integer('songCount', 20),
            $request->integer('songOffset'),
            $user,
        );

        return SubsonicResponse::ok([
            'searchResult2' => SearchResult2Resource::toArray($artists, $albums, $songs, $user),
        ]);
    }
}
