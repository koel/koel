<?php

namespace App\Http\Controllers\API\Interaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\BatchInteractionRequest;
use App\Models\User;
use App\Services\InteractionService;
use Illuminate\Contracts\Auth\Authenticatable;

class BatchLikeController extends Controller
{
    /** @param User $user */
    public function __construct(private InteractionService $interactionService, protected ?Authenticatable $user)
    {
    }

    public function store(BatchInteractionRequest $request)
    {
        $interactions = $this->interactionService->batchLike((array) $request->songs, $this->user);

        return response()->json($interactions);
    }

    public function destroy(BatchInteractionRequest $request)
    {
        $this->interactionService->batchUnlike((array) $request->songs, $this->user);

        return response()->noContent();
    }
}
