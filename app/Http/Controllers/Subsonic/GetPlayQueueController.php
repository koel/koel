<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Responses\Subsonic\Resources\PlayQueueResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Services\QueueService;
use Illuminate\Contracts\Auth\Authenticatable;

class GetPlayQueueController extends Controller
{
    public function __construct(
        private readonly QueueService $queueService,
    ) {}

    /** @param User $user */
    public function __invoke(Authenticatable $user)
    {
        $state = $this->queueService->getQueueState($user);

        if ($state->playables->isEmpty() && !$state->currentPlayable) {
            return SubsonicResponse::ok();
        }

        return SubsonicResponse::ok([
            'playQueue' => PlayQueueResource::toArray($state, $user),
        ]);
    }
}
