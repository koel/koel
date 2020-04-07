<?php

namespace App\Http\Controllers\API\MediaInformation;

use App\Models\Artist;
use Illuminate\Http\JsonResponse;

/**
 * @group 5. Media information
 */
class ArtistController extends Controller
{
    /**
     * Get artist's extra information.
     *
     * Get extra information about an artist via Last.fm.
     *
     * @responseFile responses/mediaInformation.artist.show.json
     *
     * @return JsonResponse
     */
    public function show(Artist $artist)
    {
        return response()->json($this->mediaInformationService->getArtistInformation($artist));
    }
}
