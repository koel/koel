<?php

namespace App\Http\Controllers\API\Interaction;

use App\Http\Requests\API\SongLikeRequest;
use Illuminate\Http\JsonResponse;

/**
 * @group 3. Song interactions
 */
class LikeController extends Controller
{
    /**
     * Like or unlike a song
     *
     * An "interaction" record including the song and current user's data will be returned.
     *
     * @bodyParam song string required The ID of the song. Example: 0146d01afb742b01f28ab8b556f9a75d
     *
     * @responseFile responses/interaction.json
     *
     * @return JsonResponse
     */
    public function store(SongLikeRequest $request)
    {
        return response()->json($this->interactionService->toggleLike($request->song, $request->user()));
    }
}
