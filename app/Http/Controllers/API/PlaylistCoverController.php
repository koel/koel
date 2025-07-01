<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\PlaylistCoverUpdateRequest;
use App\Models\Playlist;
use App\Services\ArtworkService;

class PlaylistCoverController extends Controller
{
    public function __construct(private readonly ArtworkService $artworkService)
    {
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
        $this->artworkService->deletePlaylistCover($playlist);

        return response()->noContent();
    }
}
