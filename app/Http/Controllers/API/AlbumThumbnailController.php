<?php

namespace App\Http\Controllers\API;

use App\Models\Album;
use App\Services\MediaMetadataService;
use Illuminate\Http\JsonResponse;

class AlbumThumbnailController extends Controller
{
    private MediaMetadataService $mediaMetadataService;

    public function __construct(MediaMetadataService $mediaMetadataService)
    {
        $this->mediaMetadataService = $mediaMetadataService;
    }

    public function show(Album $album): JsonResponse
    {
        return response()->json(['thumbnailUrl' => $this->mediaMetadataService->getAlbumThumbnailUrl($album)]);
    }
}
