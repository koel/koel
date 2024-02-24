<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\UploadPlaylistCoverRequest;
use App\Models\Playlist;
use App\Services\MediaMetadataService;

class UploadPlaylistCoverController extends Controller
{
    public function __invoke(
        UploadPlaylistCoverRequest $request,
        Playlist $playlist,
        MediaMetadataService $mediaMetadataService
    ) {
        $this->authorize('collaborate', $playlist);

        $mediaMetadataService->writePlaylistCover(
            $playlist,
            $request->getFileContentAsBinaryString(),
            $request->getFileExtension()
        );

        return response()->json(['cover_url' => $playlist->cover]);
    }
}
