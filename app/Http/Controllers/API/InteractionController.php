<?php

namespace App\Http\Controllers\API;

use App\Events\SongStartedPlaying;
use App\Http\Requests\API\BatchInteractionRequest;
use App\Models\Interaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InteractionController extends Controller
{
    /**
     * Increase a song's play count as the currently authenticated user.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function play(Request $request)
    {
        $interaction = Interaction::increasePlayCount($request->song, $request->user());
        if ($interaction) {
            event(new SongStartedPlaying($interaction->song, $interaction->user));
        }

        return response()->json($interaction);
    }

    /**
     * Like or unlike a song as the currently authenticated user.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function like(Request $request)
    {
        return response()->json(Interaction::toggleLike($request->song, $request->user()));
    }

    /**
     * Like several songs at once as the currently authenticated user.
     *
     * @param BatchInteractionRequest $request
     *
     * @return JsonResponse
     */
    public function batchLike(BatchInteractionRequest $request)
    {
        return response()->json(Interaction::batchLike((array) $request->songs, $request->user()));
    }

    /**
     * Unlike several songs at once as the currently authenticated user.
     *
     * @param BatchInteractionRequest $request
     *
     * @return JsonResponse
     */
    public function batchUnlike(BatchInteractionRequest $request)
    {
        return response()->json(Interaction::batchUnlike((array) $request->songs, $request->user()));
    }
}
