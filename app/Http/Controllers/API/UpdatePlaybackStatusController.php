<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\UpdatePlaybackStatusRequest;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\PodcastService;
use App\Services\QueueService;
use Illuminate\Contracts\Auth\Authenticatable;

class UpdatePlaybackStatusController extends Controller
{
    /** @param User $user */
    public function __invoke(
        UpdatePlaybackStatusRequest $request,
        SongRepository $songRepository,
        QueueService $queueService,
        PodcastService $podcastService,
        Authenticatable $user
    ) {
        $song = $songRepository->getOne($request->song, $user);
        $this->authorize('access', $song);

        $queueService->updatePlaybackStatus($user, $song, $request->position);

        if ($song->isEpisode()) {
            $podcastService->updateEpisodeProgress($user, $song, $request->position);
        }

        return response()->noContent();
    }
}
