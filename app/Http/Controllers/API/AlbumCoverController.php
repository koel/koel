<?php

namespace App\Http\Controllers\API;

use App\Events\LibraryChanged;
use App\Http\Requests\API\AlbumCoverUpdateRequest;
use App\Models\Album;
use App\Services\MediaMetadataService;
use Illuminate\Http\JsonResponse;

class AlbumCoverController extends Controller
{
    private MediaMetadataService $mediaMetadataService;

    public function __construct(MediaMetadataService $mediaMetadataService)
    {
        $this->mediaMetadataService = $mediaMetadataService;
    }

    public function update(AlbumCoverUpdateRequest $request, Album $album)
    {
        $this->mediaMetadataService->writeAlbumCover(
            $album,
            $request->getFileContentAsBinaryString(),
            $request->getFileExtension()
        );

        event(new LibraryChanged());

        return new JsonResponse(['coverUrl' => $album->cover]);
    }
}
