<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Services\AlbumService;

class FetchAlbumThumbnailController extends Controller
{
    public function __invoke(Album $album, AlbumService $albumService)
    {
        $this->authorize('access', $album);

        return response()->json([
            'thumbnailUrl' => image_storage_url($albumService->getOrCreateAlbumThumbnail($album)), // @todo snake_case
        ]);
    }
}
