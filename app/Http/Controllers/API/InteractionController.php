<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\BatchInteractionRequest;
use App\Models\Interaction;
use Illuminate\Http\Request;

class InteractionController extends Controller
{
    /**
     * Increase a song's play count as the currently authenticated user.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function play(Request $request)
    {
        return response()->json(Interaction::increasePlayCount($request->input('id')));
    }

    /**
     * Like or unlike a song as the currently authenticated user.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function like(Request $request)
    {
        return response()->json(Interaction::toggleLike($request->input('id')));
    }

    /**
     * Like several songs at once as the currently authenticated user.
     *
     * @param BatchInteractionRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function batchLike(BatchInteractionRequest $request)
    {
        return response()->json(Interaction::batchLike((array) $request->input('ids')));
    }

    /**
     * Unlike several songs at once as the currently authenticated user.
     *
     * @param BatchInteractionRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function batchUnlike(BatchInteractionRequest $request)
    {
        return response()->json(Interaction::batchUnlike((array) $request->input('ids')));
    }
}
