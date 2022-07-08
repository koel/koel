<?php

namespace App\Http\Controllers\V6\API;

use App\Http\Controllers\API\Controller;
use App\Models\Album;
use App\Services\MediaInformationService;

class FetchAlbumInformationController extends Controller
{
    public function __invoke(Album $album, MediaInformationService $informationService)
    {
        return response()->json($informationService->getAlbumInformation($album));
    }
}
