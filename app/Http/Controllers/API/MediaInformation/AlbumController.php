<?php

namespace App\Http\Controllers\API\MediaInformation;

use App\Models\Album;

class AlbumController extends Controller
{
    public function show(Album $album)
    {
        return response()->json($this->mediaInformationService->getAlbumInformation($album));
    }
}
