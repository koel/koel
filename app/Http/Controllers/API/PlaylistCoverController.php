<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\PlaylistCoverUpdateRequest;
use App\Models\Playlist;
use App\Services\MediaMetadataService;

class PlaylistCoverController extends Controller
{
    public function __construct(private readonly MediaMetadataService $mediaMetadataService)
    {
    }

    public function update(PlaylistCoverUpdateRequest $request, Playlist $playlist)
    {
        $this->authorize('own', $playlist);
        $this->mediaMetadataService->writePlaylistCover($playlist, $request->getFileContent());

        return response()->json(['cover_url' => $playlist->cover]);
    }

    public function destroy(Playlist $playlist)
    {
        $this->authorize('own', $playlist);
        $this->mediaMetadataService->deletePlaylistCover($playlist);

        return response()->noContent();
    }
}
