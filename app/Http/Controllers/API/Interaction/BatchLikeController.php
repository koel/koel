<?php

namespace App\Http\Controllers\API\Interaction;

use App\Http\Requests\API\BatchInteractionRequest;
use Illuminate\Http\Response;

class BatchLikeController extends Controller
{
    public function store(BatchInteractionRequest $request)
    {
        $interactions = $this->interactionService->batchLike((array) $request->songs, $this->currentUser);

        return response()->json($interactions);
    }

    public function destroy(BatchInteractionRequest $request)
    {
        $this->interactionService->batchUnlike((array) $request->songs, $this->currentUser);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
