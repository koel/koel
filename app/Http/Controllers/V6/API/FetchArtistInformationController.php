<?php

namespace App\Http\Controllers\V6\API;

use App\Http\Controllers\API\Controller;
use App\Models\Artist;
use App\Services\MediaInformationService;

class FetchArtistInformationController extends Controller
{
    public function __invoke(Artist $artist, MediaInformationService $informationService)
    {
        return response()->json($informationService->getArtistInformation($artist));
    }
}
