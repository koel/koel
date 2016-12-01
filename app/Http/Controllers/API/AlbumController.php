<?php

namespace App\Http\Controllers\API;

use App\Models\Album;

class AlbumController extends Controller
{
    /**
     * Get extra information about an album via Last.fm.
     *
     * @param Album $album
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInfo(Album $album)
    {
        return response()->json($album->getInfo());
    }
}
