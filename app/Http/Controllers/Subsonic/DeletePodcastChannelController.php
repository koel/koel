<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\IdRequest;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Repositories\PodcastRepository;
use App\Services\Podcast\PodcastService;
use Illuminate\Contracts\Auth\Authenticatable;

class DeletePodcastChannelController extends Controller
{
    public function __construct(
        private readonly PodcastRepository $podcastRepository,
        private readonly PodcastService $podcastService,
    ) {}

    /** @param User $user */
    public function __invoke(IdRequest $request, Authenticatable $user)
    {
        $podcast = $this->podcastRepository->getMany([$request->id], user: $user)->first();

        if (!$podcast) {
            return SubsonicResponse::error(70, 'Podcast not found.');
        }

        $this->podcastService->unsubscribeUserFromPodcast($user, $podcast);

        return SubsonicResponse::ok();
    }
}
