<?php

namespace App\Http\Controllers\API\Interaction;

use App\Events\SongStartedPlaying;
use App\Http\Requests\API\Interaction\StorePlayCountRequest;

class PlayCountController extends Controller
{
    public function store(StorePlayCountRequest $request)
    {
        $interaction = $this->interactionService->increasePlayCount($request->song, $this->user);
        event(new SongStartedPlaying($interaction->song, $interaction->user));

        return response()->json($interaction);
    }
}
