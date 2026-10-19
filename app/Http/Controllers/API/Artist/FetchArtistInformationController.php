<?php

namespace App\Http\Controllers\API\Artist;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Services\Integrations\EncyclopediaService;

class FetchArtistInformationController extends Controller
{
    public function __invoke(Artist $artist, EncyclopediaService $encyclopediaService)
    {
        $this->authorize('access', $artist);

        return response()->json($encyclopediaService->getArtistInformation($artist));
    }
}
