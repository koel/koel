<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Services\MediaInformationService;

class FetchAlbumInformationController extends Controller
{
    public function __invoke(Album $album, MediaInformationService $informationService)
    {
        return response()->json($informationService->getAlbumInformation($album));
    }
}
