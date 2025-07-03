<?php

namespace App\Http\Controllers\API\Podcast;

use App\Http\Controllers\Controller;
use App\Http\Resources\SongResourceCollection;
use App\Models\Podcast;
use App\Repositories\SongRepository;
use App\Services\PodcastService;

class PodcastEpisodeController extends Controller
{
    public function __construct(
        private readonly SongRepository $episodeRepository,
        private readonly PodcastService $podcastService
    ) {
    }

    public function index(Podcast $podcast)
    {
        if (request()->get('refresh')) {
            $this->podcastService->refreshPodcast($podcast);
        }

        return SongResourceCollection::make($this->episodeRepository->getEpisodesByPodcast($podcast));
    }
}
