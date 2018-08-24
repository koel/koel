<?php

namespace App\Http\Controllers\API\Interaction;

use App\Http\Requests\API\SongLikeRequest;
use Illuminate\Http\JsonResponse;

class LikeController extends Controller
{
    /**
     * Like or unlike a song as the currently authenticated user.
     *
     * @return JsonResponse
     */
    public function store(SongLikeRequest $request)
    {
        return response()->json($this->interactionService->toggleLike($request->song, $request->user()));
    }
}
