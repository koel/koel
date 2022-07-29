<?php

namespace App\Http\Controllers\API\MediaInformation;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Services\MediaInformationService;

class ArtistController extends Controller
{
    public function __construct(private MediaInformationService $mediaInformationService)
    {
    }

    public function show(Artist $artist)
    {
        return response()->json($this->mediaInformationService->getArtistInformation($artist)?->toArray() ?: []);
    }
}
