<?php

namespace App\Http\Controllers\API\Artist;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Services\EncyclopediaService;

class FetchArtistInformationController extends Controller
{
    public function __invoke(Artist $artist, EncyclopediaService $encyclopediaService)
    {
        return response()->json($encyclopediaService->getArtistInformation($artist));
    }
}
