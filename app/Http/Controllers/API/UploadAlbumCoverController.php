<?php

namespace App\Http\Controllers\API;

use App\Events\LibraryChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\UploadAlbumCoverRequest;
use App\Models\Album;
use App\Services\MediaMetadataService;

class UploadAlbumCoverController extends Controller
{
    public function __invoke(UploadAlbumCoverRequest $request, Album $album, MediaMetadataService $mediaMetadataService)
    {
        $mediaMetadataService->writeAlbumCover(
            $album,
            $request->getFileContentAsBinaryString(),
            $request->getFileExtension()
        );

        event(new LibraryChanged());

        return response()->json(['coverUrl' => $album->cover]);
    }
}
