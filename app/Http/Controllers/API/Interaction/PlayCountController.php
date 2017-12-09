<?php

namespace App\Http\Controllers\API\Interaction;

use App\Events\SongStartedPlaying;
use App\Http\Requests\API\Interaction\StorePlayCountRequest;
use App\Models\Interaction;
use Illuminate\Http\JsonResponse;

class PlayCountController extends Controller
{
    /**
     * Increase a song's play count as the currently authenticated user.
     *
     * @param StorePlayCountRequest $request
     *
     * @return JsonResponse
     */
    public function store(StorePlayCountRequest $request)
    {
        $interaction = Interaction::increasePlayCount($request->song, $request->user());
        if ($interaction) {
            event(new SongStartedPlaying($interaction->song, $interaction->user));
        }

        return response()->json($interaction);
    }
}
