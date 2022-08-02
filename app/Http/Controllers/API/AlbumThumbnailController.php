<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Services\MediaMetadataService;
use Illuminate\Http\JsonResponse;

class AlbumThumbnailController extends Controller
{
    public function __construct(private MediaMetadataService $mediaMetadataService)
    {
    }

    public function show(Album $album): JsonResponse
    {
        return response()->json(['thumbnailUrl' => $this->mediaMetadataService->getAlbumThumbnailUrl($album)]);
    }
}
