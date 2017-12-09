<?php

namespace App\Http\Controllers\API\Interaction;

use App\Http\Requests\API\SongLikeRequest;
use App\Models\Interaction;
use Illuminate\Http\JsonResponse;

class LikeController extends Controller
{
    /**
     * Like or unlike a song as the currently authenticated user.
     *
     * @param SongLikeRequest $request
     *
     * @return JsonResponse
     */
    public function store(SongLikeRequest $request)
    {
        return response()->json(Interaction::toggleLike($request->song, $request->user()));
    }
}
