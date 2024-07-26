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
        $this->authorize('own', $playlist);
        $mediaMetadataService->writePlaylistCover($playlist, $request->getFileContent());

        return response()->json(['cover_url' => $playlist->cover]);
    }
}
