<?php

namespace App\Http\Controllers\API\Interaction;

use App\Http\Requests\API\SongLikeRequest;

class LikeController extends Controller
{
    public function store(SongLikeRequest $request)
    {
        return response()->json($this->interactionService->toggleLike($request->song, $this->currentUser));
    }
}
