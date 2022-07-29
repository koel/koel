<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\PlaylistSongUpdateRequest;
use App\Models\Playlist;
use App\Services\PlaylistService;
use App\Services\SmartPlaylistService;

class PlaylistSongController extends Controller
{
    public function __construct(
        private SmartPlaylistService $smartPlaylistService,
        private PlaylistService $playlistService
    ) {
    }

    public function index(Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        return response()->json(
            $playlist->is_smart
                ? $this->smartPlaylistService->getSongs($playlist)->pluck('id')
                : $playlist->songs->pluck('id')
        );
    }

    /** @deprecated */
    public function update(PlaylistSongUpdateRequest $request, Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        abort_if($playlist->is_smart, 403, 'A smart playlist cannot be populated manually.');

        $this->playlistService->populatePlaylist($playlist, (array) $request->songs);

        return response()->noContent();
    }
}
