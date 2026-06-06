<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\CreatePodcastChannelRequest;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Services\Podcast\PodcastService;
use Illuminate\Contracts\Auth\Authenticatable;

class CreatePodcastChannelController extends Controller
{
    public function __construct(
        private readonly PodcastService $podcastService,
    ) {}

    /** @param User $user */
    public function __invoke(CreatePodcastChannelRequest $request, Authenticatable $user)
    {
        $this->podcastService->addPodcast($request->url, $user);

        return SubsonicResponse::ok();
    }
}
