<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Services\ArtworkService;

class FetchAlbumThumbnailController extends Controller
{
    public function __invoke(Album $album, ArtworkService $artworkService)
    {
        return response()->json(['thumbnailUrl' => $artworkService->getAlbumThumbnailUrl($album)]);
    }
}
