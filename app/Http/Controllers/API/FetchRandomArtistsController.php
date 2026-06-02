<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArtistResource;
use App\Repositories\ArtistRepository;

class FetchRandomArtistsController extends Controller
{
    public function __invoke(ArtistRepository $artistRepository)
    {
        return ArtistResource::collection($artistRepository->getRandom(15));
    }
}
