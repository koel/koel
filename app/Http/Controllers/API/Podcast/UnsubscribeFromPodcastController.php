<?php

namespace App\Http\Controllers\API\Podcast;

use App\Http\Controllers\Controller;
use App\Models\Podcast\Podcast;
use App\Models\User;
use App\Services\PodcastService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

class UnsubscribeFromPodcastController extends Controller
{
    /** @param User $user */
    public function __invoke(Podcast $podcast, PodcastService $podcastService, Authenticatable $user)
    {
        abort_unless($user->subscribedToPodcast($podcast), Response::HTTP_BAD_REQUEST);

        $podcastService->unsubscribeUserFromPodcast($user, $podcast);

        return response()->json();
    }
}
