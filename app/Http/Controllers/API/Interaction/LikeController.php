<?php

namespace App\Http\Controllers\API\Interaction;

use App\Models\Interaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    /**
     * Like or unlike a song as the currently authenticated user.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        return response()->json(Interaction::toggleLike($request->song, $request->user()));
    }
}
