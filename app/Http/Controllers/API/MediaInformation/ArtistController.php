<?php

namespace App\Http\Controllers\API\MediaInformation;

use App\Models\Artist;
use Illuminate\Http\JsonResponse;

class ArtistController extends Controller
{
    /**
     * Get extra information about an artist via Last.fm.
     *
     * @param Artist $artist
     *
     * @return JsonResponse
     */
    public function show(Artist $artist)
    {
        return response()->json($this->mediaInformationService->getArtistInformation($artist));
    }
}
