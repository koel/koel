<?php

namespace App\Http\Controllers\API;

use App\Models\Album;
use Illuminate\Http\JsonResponse;

class AlbumController extends Controller
{
    /**
     * Get extra information about an album via Last.fm.
     *
     * @param Album $album
     *
     * @return JsonResponse
     */
    public function getInfo(Album $album)
    {
        return response()->json($album->getInfo());
    }
}
