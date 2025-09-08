<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Services\ImageStorage;

class FetchAlbumThumbnailController extends Controller
{
    public function __invoke(Album $album, ImageStorage $imageStorage)
    {
        return response()->json(['thumbnailUrl' => $imageStorage->getAlbumThumbnailUrl($album)]);
    }
}
