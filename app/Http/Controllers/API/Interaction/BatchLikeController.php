<?php

namespace App\Http\Controllers\API\Interaction;

use App\Http\Requests\API\BatchInteractionRequest;

class BatchLikeController extends Controller
{
    /**
     * Like several songs at once as the currently authenticated user.
     */
    public function store(BatchInteractionRequest $request)
    {
        $interactions = $this->interactionService->batchLike((array) $request->songs, $request->user());

        return response()->json($interactions);
    }

    /**
     * Unlike several songs at once as the currently authenticated user.
     */
    public function destroy(BatchInteractionRequest $request)
    {
        $this->interactionService->batchUnlike((array) $request->songs, $request->user());

        return response()->json();
    }
}
