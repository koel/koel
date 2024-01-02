<?php

namespace App\Http\Controllers\API\Interaction;

use App\Events\PlaybackStarted;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Interaction\IncreasePlayCountRequest;
use App\Http\Resources\InteractionResource;
use App\Models\User;
use App\Services\InteractionService;
use Illuminate\Contracts\Auth\Authenticatable;

class HandlePlaybackStartedController extends Controller
{
    /** @param User $user */
    public function __invoke(
        IncreasePlayCountRequest $request,
        InteractionService $interactionService,
        Authenticatable $user
    ) {
        $interaction = $interactionService->increasePlayCount($request->song, $user);
        event(new PlaybackStarted($interaction->song, $interaction->user));

        return InteractionResource::make($interaction);
    }
}
