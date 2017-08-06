<?php

namespace App\Http\Controllers\API\Interaction;

use App\Http\Requests\API\BatchInteractionRequest;
use App\Models\Interaction;
use Illuminate\Http\JsonResponse;

class BatchLikeController extends Controller
{
    /**
     * Like several songs at once as the currently authenticated user.
     *
     * @param BatchInteractionRequest $request
     *
     * @return JsonResponse
     */
    public function store(BatchInteractionRequest $request)
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
    public function destroy(BatchInteractionRequest $request)
    {
        return response()->json(Interaction::batchUnlike((array) $request->songs, $request->user()));
    }
}
