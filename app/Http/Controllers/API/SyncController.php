<?php

namespace App\Http\Controllers\API;

use App\Facades\Media;
use App\Http\Requests\API\SyncRequest;

class SyncController extends Controller
{
    /**
     * Synchronize the library.
     *
     * @param Request $request
     * @param bool    $force
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sync(SyncRequest $request, $force = false)
    {
        // In a next version we should opt for a echo system,
        // but let's just do this async now.
        $results = Media::sync();

        return response()->json();
    }
}
