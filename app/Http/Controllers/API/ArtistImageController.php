<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Artist\ArtistImageStoreRequest;
use App\Models\Artist;
use App\Services\ArtistService;
use App\Services\ArtworkService;

class ArtistImageController extends Controller
{
    public function __construct(
        private readonly ArtistService $artistService,
        private readonly ArtworkService $artworkService
    ) {
    }

    public function store(ArtistImageStoreRequest $request, Artist $artist)
    {
        $this->authorize('update', $artist);
        $this->artworkService->storeArtistImage($artist, $request->getFileContent());

        return response()->json(['image_url' => $artist->image]);
    }

    public function destroy(Artist $artist)
    {
        $this->authorize('update', $artist);
        $this->artistService->removeArtistImage($artist);

        return response()->noContent();
    }
}
