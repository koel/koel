<?php

namespace App\Http\Controllers\API;

use App\Enums\PlayableType;
use App\Http\Controllers\Controller;
use App\Http\Resources\AlbumResource;
use App\Http\Resources\ArtistResource;
use App\Http\Resources\PodcastResource;
use App\Http\Resources\RadioStationResource;
use App\Http\Resources\SongResource;
use App\Models\User;
use App\Repositories\AlbumRepository;
use App\Repositories\ArtistRepository;
use App\Repositories\PodcastRepository;
use App\Repositories\RadioStationRepository;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

class FetchOverviewController extends Controller
{
    /** @param User $user */
    public function __invoke(
        SongRepository $songRepository,
        AlbumRepository $albumRepository,
        ArtistRepository $artistRepository,
        PodcastRepository $podcastRepository,
        RadioStationRepository $radioStationRepository,
        Authenticatable $user,
    ) {
        $recentlyPlayed = $songRepository->getRecentlyPlayed(6, type: PlayableType::SONG);
        $usesPodcasts = (bool) config('koel.podcasts.enabled');
        $usesRadio = (bool) config('koel.radio.enabled');

        return response()->json([
            'most_played_albums' => AlbumResource::collection($albumRepository->getMostPlayed(15)),
            'most_played_artists' => ArtistResource::collection($artistRepository->getMostPlayed(15)),
            'most_played_songs' => SongResource::collection($songRepository->getMostPlayed(
                6,
                type: PlayableType::SONG,
            )),
            'random_albums' => AlbumResource::collection($albumRepository->getRandom(15)),
            'random_artists' => ArtistResource::collection($artistRepository->getRandom(15)),
            'recently_added_albums' => AlbumResource::collection($albumRepository->getRecentlyAdded(15)),
            'recently_added_artists' => ArtistResource::collection($artistRepository->getRecentlyAdded(15)),
            'recently_added_songs' => SongResource::collection($songRepository->getRecentlyAdded(6)),
            'recently_added_episodes' => SongResource::collection(
                $usesPodcasts ? $songRepository->getNewestEpisodesForUser(6, $user) : Collection::empty(),
            ),
            'recently_subscribed_podcasts' => PodcastResource::collection(
                $usesPodcasts ? $podcastRepository->getRecentlySubscribedByUser(15, $user) : Collection::empty(),
            ),
            'recently_added_radio_stations' => RadioStationResource::collection(
                $usesRadio ? $radioStationRepository->getRecentlyAddedForUser(15, $user) : Collection::empty(),
            ),
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
