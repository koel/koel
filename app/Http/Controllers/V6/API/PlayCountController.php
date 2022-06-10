<?php

namespace App\Http\Controllers\V6\API;

use App\Events\SongStartedPlaying;
use App\Http\Controllers\API\Interaction\Controller;
use App\Http\Requests\API\Interaction\StorePlayCountRequest;
use App\Http\Resources\InteractionResource;

class PlayCountController extends Controller
{
    public function store(StorePlayCountRequest $request)
    {
        $interaction = $this->interactionService->increasePlayCount($request->song, $this->user);
        event(new SongStartedPlaying($interaction->song, $interaction->user));

        return InteractionResource::make($interaction);
    }
}
