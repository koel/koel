<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Services\EncyclopediaService;

class FetchAlbumInformationController extends Controller
{
    public function __invoke(Album $album, EncyclopediaService $encyclopediaService)
    {
        return response()->json($encyclopediaService->getAlbumInformation($album));
    }
}
