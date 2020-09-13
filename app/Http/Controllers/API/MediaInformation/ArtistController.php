<?php

namespace App\Http\Controllers\API\MediaInformation;

use App\Models\Artist;

class ArtistController extends Controller
{
    public function show(Artist $artist)
    {
        return response()->json($this->mediaInformationService->getArtistInformation($artist));
    }
}
