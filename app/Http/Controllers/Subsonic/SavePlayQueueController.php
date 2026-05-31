<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subsonic\SavePlayQueueRequest;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\QueueService;
use Illuminate\Contracts\Auth\Authenticatable;

class SavePlayQueueController extends Controller
{
    public function __construct(
        private readonly QueueService $queueService,
        private readonly SongRepository $songRepository,
    ) {}

    /** @param User $user */
    public function __invoke(SavePlayQueueRequest $request, Authenticatable $user)
    {
        $current = $request->current ? $this->songRepository->findOne($request->current, $user) : null;

        $positionSeconds = (int) round($request->position / 1000);
        $clientName = (string) $request->input('c') ?: null;

        $this->queueService->savePlayQueue(
            user: $user,
            songIds: $request->id,
            currentSong: $current,
            position: $positionSeconds,
            clientName: $clientName,
        );

        return SubsonicResponse::ok();
    }
}
