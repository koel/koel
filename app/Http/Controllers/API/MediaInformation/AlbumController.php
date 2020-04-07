<?php

namespace App\Http\Controllers\API\MediaInformation;

use App\Models\Album;
use Illuminate\Http\JsonResponse;

/**
 * @group 5. Media information
 */
class AlbumController extends Controller
{
    /**
     * Get album's extra information.
     *
     * Get extra information about an album via Last.fm.
     *
     * @responseFile responses/mediaInformation.album.show.json
     *
     * @return JsonResponse
     */
    public function show(Album $album)
    {
        return response()->json($this->mediaInformationService->getAlbumInformation($album));
    }
}
