<?php

namespace App\Http\Controllers\API\Interaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\SongLikeRequest;
use App\Models\User;
use App\Services\InteractionService;
use Illuminate\Contracts\Auth\Authenticatable;

class LikeController extends Controller
{
    /** @param User $user */
    public function __construct(private InteractionService $interactionService, private ?Authenticatable $user)
    {
    }

    public function store(SongLikeRequest $request)
    {
        return response()->json($this->interactionService->toggleLike($request->song, $this->user));
    }
}
