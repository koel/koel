<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Playlist\PlaylistCoverUpdateRequest;
use App\Models\Playlist;
use App\Services\PlaylistService;

class PlaylistCoverController extends Controller
{
    public function __construct(
        private readonly PlaylistService $playlistService,
    ) {
    }

    public function update(PlaylistCoverUpdateRequest $request, Playlist $playlist)
    {
        $this->authorize('own', $playlist);
        $updated = $this->playlistService->updatePlaylistCover($playlist, $request->getFileContent());

        return response()->json(['cover_url' => $updated->cover]);
    }

    public function destroy(Playlist $playlist)
    {
        $this->authorize('own', $playlist);
        $this->playlistService->deletePlaylistCover($playlist);

        return response()->noContent();
    }
}
