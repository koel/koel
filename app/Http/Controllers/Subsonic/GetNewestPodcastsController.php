<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\GetNewestPodcastsRequest;
use App\Http\Responses\Subsonic\Resources\PodcastEpisodeResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Repositories\SongRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class GetNewestPodcastsController extends Controller
{
    public function __construct(
        private readonly SongRepository $songRepository,
    ) {}

    /** @param User $user */
    public function __invoke(GetNewestPodcastsRequest $request, Authenticatable $user)
    {
        $episodes = $this->songRepository->getNewestEpisodesForUser($request->count, $user);

        return SubsonicResponse::ok([
            'newestPodcasts' => [
                'episode' => $episodes->map(PodcastEpisodeResource::toArray(...))->all(),
            ],
        ]);
    }
}
