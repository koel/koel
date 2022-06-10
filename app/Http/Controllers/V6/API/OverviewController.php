<?php

namespace App\Http\Controllers\V6\API;

use App\Http\Controllers\API\Controller;
use App\Http\Resources\AlbumResource;
use App\Http\Resources\ArtistResource;
use App\Http\Resources\SongResource;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\SongRepository;

class OverviewController extends Controller
{
    public function __construct(
        private SongRepository $songRepository,
        private AlbumRepository $albumRepository,
        private ArtistRepository $artistRepository
    ) {
    }

    public function index()
    {
        return response()->json([
            'most_played_songs' => SongResource::collection($this->songRepository->getMostPlayed()),
            'recently_played_songs' => SongResource::collection($this->songRepository->getRecentlyPlayed()),
            'recently_added_albums' => AlbumResource::collection($this->albumRepository->getRecentlyAdded()),
            'recently_added_songs' => SongResource::collection($this->songRepository->getRecentlyAdded()),
            'most_played_artists' => ArtistResource::collection($this->artistRepository->getMostPlayed()),
            'most_played_albums' => AlbumResource::collection($this->albumRepository->getMostPlayed()),
        ]);
    }
}
