<?php

namespace App\Http\Controllers\API\MediaInformation;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Services\MediaInformationService;

class AlbumController extends Controller
{
    public function __construct(private MediaInformationService $mediaInformationService)
    {
    }

    public function show(Album $album)
    {
        return response()->json($this->mediaInformationService->getAlbumInformation($album)?->toArray() ?: []);
    }
}
