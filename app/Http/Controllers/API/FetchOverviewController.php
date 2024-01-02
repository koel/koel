<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlbumResource;
use App\Http\Resources\ArtistResource;
use App\Http\Resources\SongResource;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\SongRepository;

class FetchOverviewController extends Controller
{
    public function __invoke(
        SongRepository $songRepository,
        AlbumRepository $albumRepository,
        ArtistRepository $artistRepository
    ) {

        return response()->json([
            'most_played_songs' => SongResource::collection($songRepository->getMostPlayed()),
            'recently_played_songs' => SongResource::collection($songRepository->getRecentlyPlayed()),
            'recently_added_albums' => AlbumResource::collection($albumRepository->getRecentlyAdded()),
            'recently_added_songs' => SongResource::collection($songRepository->getRecentlyAdded()),
            'most_played_artists' => ArtistResource::collection($artistRepository->getMostPlayed()),
            'most_played_albums' => AlbumResource::collection($albumRepository->getMostPlayed()),
        ]);
    }
}
