<?php

namespace App\Http\Controllers\API\Interaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ToggleLikeSongRequest;
use App\Models\User;
use App\Services\InteractionService;
use Illuminate\Contracts\Auth\Authenticatable;

class ToggleLikeSongController extends Controller
{
    /** @param User $user */
    public function __invoke(
        ToggleLikeSongRequest $request,
        InteractionService $interactionService,
        Authenticatable $user
    ) {
        return response()->json($interactionService->toggleLike($request->song, $user));
    }
}
