<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\GetPodcastsRequest;
use App\Http\Responses\Subsonic\Resources\PodcastChannelResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\Podcast;
use App\Models\User;
use App\Repositories\PodcastRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class GetPodcastsController extends Controller
{
    public function __construct(
        private readonly PodcastRepository $podcastRepository,
    ) {}

    /** @param User $user */
    public function __invoke(GetPodcastsRequest $request, Authenticatable $user)
    {
        $podcasts = $request->id
            ? $this->podcastRepository->getMany([$request->id], user: $user)
            : $this->podcastRepository->getAllSubscribedByUser(favoritesOnly: false, user: $user);

        if ($request->includeEpisodes) {
            $podcasts->load(['episodes']);
        }

        return SubsonicResponse::ok([
            'podcasts' => [
                'channel' => $podcasts->map(static fn (Podcast $podcast) => PodcastChannelResource::toArray(
                    $podcast,
                    $request->includeEpisodes,
                ))->all(),
            ],
        ]);
    }
}
