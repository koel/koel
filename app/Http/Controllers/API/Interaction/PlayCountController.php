<?php

namespace App\Http\Controllers\API\Interaction;

use App\Events\SongStartedPlaying;
use App\Http\Requests\API\Interaction\StorePlayCountRequest;
use Illuminate\Http\JsonResponse;

/**
 * @group 3. Song interactions
 */
class PlayCountController extends Controller
{
    /**
     * Increase play count.
     *
     * Increase a song's play count as the currently authenticated user.
     * This request should be made whenever a song is played.
     * An "interaction" record including the song and current user's data will be returned.
     *
     * @bodyParam song string required The ID of the song. Example: 0146d01afb742b01f28ab8b556f9a75d
     *
     * @responseFile responses/interaction.json
     *
     * @return JsonResponse
     */
    public function store(StorePlayCountRequest $request)
    {
        $interaction = $this->interactionService->increasePlayCount($request->song, $request->user());
        event(new SongStartedPlaying($interaction->song, $interaction->user));

        return response()->json($interaction);
    }
}
