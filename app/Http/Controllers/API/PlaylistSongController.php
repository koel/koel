<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\PlaylistSongUpdateRequest;
use App\Models\Playlist;
use App\Services\SmartPlaylistService;

class PlaylistSongController extends Controller
{
    private SmartPlaylistService $smartPlaylistService;

    public function __construct(SmartPlaylistService $smartPlaylistService)
    {
        $this->smartPlaylistService = $smartPlaylistService;
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

    public function update(PlaylistSongUpdateRequest $request, Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        abort_if($playlist->is_smart, 403, 'A smart playlist\'s content cannot be updated manually.');

        $playlist->songs()->sync((array) $request->songs);

        return response()->json();
    }
}
