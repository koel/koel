<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\PlaylistSongUpdateRequest;
use App\Models\Playlist;
use App\Models\User;
use App\Services\PlaylistService;
use App\Services\SmartPlaylistService;
use Illuminate\Contracts\Auth\Authenticatable;

class PlaylistSongController extends Controller
{
    /** @param User $user */
    public function __construct(
        private SmartPlaylistService $smartPlaylistService,
        private PlaylistService $playlistService,
        private ?Authenticatable $user
    ) {
    }

    public function index(Playlist $playlist)
    {
        $this->authorize('own', $playlist);

        return response()->json(
            $playlist->is_smart
                ? $this->smartPlaylistService->getSongs($playlist, $this->user)->pluck('id')
                : $playlist->songs->pluck('id')
        );
    }

    /** @deprecated */
    public function update(PlaylistSongUpdateRequest $request, Playlist $playlist)
    {
        $this->authorize('own', $playlist);

        abort_if($playlist->is_smart, 403, 'A smart playlist cannot be populated manually.');

        $this->playlistService->populatePlaylist($playlist, (array) $request->songs);

        return response()->noContent();
    }
}
