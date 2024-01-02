<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Services\MediaMetadataService;

class FetchAlbumThumbnailController extends Controller
{
    public function __invoke(Album $album, MediaMetadataService $mediaMetadataService)
    {
        return response()->json(['thumbnailUrl' => $mediaMetadataService->getAlbumThumbnailUrl($album)]);
    }
}
