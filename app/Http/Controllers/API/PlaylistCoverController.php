<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Playlist\PlaylistCoverUpdateRequest;
use App\Models\Playlist;
use App\Services\ArtworkService;
use App\Services\PlaylistService;

class PlaylistCoverController extends Controller
{
    public function __construct(
        private readonly PlaylistService $playlistService,
        private readonly ArtworkService $artworkService,
    ) {
    }

    public function update(PlaylistCoverUpdateRequest $request, Playlist $playlist)
    {
        $this->authorize('own', $playlist);
        $this->artworkService->storePlaylistCover($playlist, $request->getFileContent());

        return response()->json(['cover_url' => $playlist->cover]);
    }

    public function destroy(Playlist $playlist)
    {
        $this->authorize('own', $playlist);
        $this->playlistService->deleteCover($playlist);

        return response()->noContent();
    }
}
