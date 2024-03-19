<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\UploadAlbumCoverRequest;
use App\Models\Album;
use App\Services\MediaMetadataService;

class UploadAlbumCoverController extends Controller
{
    public function __invoke(UploadAlbumCoverRequest $request, Album $album, MediaMetadataService $metadataService)
    {
        $this->authorize('update', $album);
        $metadataService->writeAlbumCover($album, $request->getFileContent());

        return response()->json(['cover_url' => $album->cover]);
    }
}
