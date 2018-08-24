<?php

namespace App\Http\Controllers\API\MediaInformation;

use App\Models\Album;
use Illuminate\Http\JsonResponse;

class AlbumController extends Controller
{
    /**
     * Get extra information about an album via Last.fm.
     *
     * @return JsonResponse
     */
    public function show(Album $album)
    {
        return response()->json($this->mediaInformationService->getAlbumInformation($album));
    }
}
