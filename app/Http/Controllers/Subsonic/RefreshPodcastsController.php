<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Repositories\PodcastRepository;
use App\Services\Podcast\PodcastService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use Throwable;

class RefreshPodcastsController extends Controller
{
    public function __construct(
        private readonly PodcastRepository $podcastRepository,
        private readonly PodcastService $podcastService,
    ) {}

    /** @param User $user */
    public function __invoke(Authenticatable $user)
    {
        $podcasts = $this->podcastRepository->getAllSubscribedByUser(favoritesOnly: false, user: $user);

        foreach ($podcasts as $podcast) {
            try {
                $this->podcastService->refreshPodcast($podcast);
            } catch (Throwable $e) {
                Log::warning("Failed to refresh podcast {$podcast->id}: {$e->getMessage()}");
            }
        }

        return SubsonicResponse::ok();
    }
}
