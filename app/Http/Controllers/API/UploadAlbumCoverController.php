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
        $this->authorize('update', $album);

        $mediaMetadataService->writeAlbumCover(
            $album,
            $request->getFileContentAsBinaryString(),
            $request->getFileExtension()
        );

        event(new LibraryChanged());

        return response()->json(['cover_url' => $album->cover]);
    }
}
