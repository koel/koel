<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\UploadAlbumCoverRequest;
use App\Models\Album;
use App\Services\MediaMetadataService;
use Illuminate\Support\Facades\Cache;

class UploadAlbumCoverController extends Controller
{
    public function __invoke(UploadAlbumCoverRequest $request, Album $album, MediaMetadataService $metadataService)
    {
        $this->authorize('update', $album);
        $metadataService->writeAlbumCover($album, $request->getFileContent());

        Cache::delete("album.info.$album->id");

        return response()->json(['cover_url' => $album->cover]);
    }
}
