<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\UploadAlbumCoverRequest;
use App\Models\Album;
use App\Services\ArtworkService;

class UploadAlbumCoverController extends Controller
{
    public function __invoke(UploadAlbumCoverRequest $request, Album $album, ArtworkService $artworkService)
    {
        $this->authorize('update', $album);
        $artworkService->storeAlbumCover($album, $request->getFileContent());

        return response()->json(['cover_url' => $album->cover]);
    }
}
