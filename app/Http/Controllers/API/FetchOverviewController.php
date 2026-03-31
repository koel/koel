<?php

namespace App\Http\Controllers\API;

use App\Enums\PlayableType;
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
        ArtistRepository $artistRepository,
    ) {
        $recentlyPlayed = $songRepository->getRecentlyPlayed(6, type: PlayableType::SONG);

        return response()->json([
            'most_played_albums' => AlbumResource::collection($albumRepository->getMostPlayed()),
            'most_played_artists' => ArtistResource::collection($artistRepository->getMostPlayed()),
            'most_played_songs' => SongResource::collection($songRepository->getMostPlayed(
                6,
                type: PlayableType::SONG,
            )),
            'recently_added_albums' => AlbumResource::collection($albumRepository->getRecentlyAdded()),
            'recently_added_artists' => ArtistResource::collection($artistRepository->getRecentlyAdded()),
            'recently_added_songs' => SongResource::collection($songRepository->getRecentlyAdded(6)),
            'recently_played_songs' => SongResource::collection($recentlyPlayed),
            'least_played_songs' => SongResource::collection($songRepository->getLeastPlayed(
                6,
                type: PlayableType::SONG,
            )),
            'random_songs' => SongResource::collection($songRepository->getRandom(6)),
            'similar_songs' => SongResource::collection($songRepository->getSimilarToMany($recentlyPlayed->take(5), 6)),
        ]);
    }
}
