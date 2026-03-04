<?php

namespace App\Http\Controllers\API;

use App\Enums\Placement;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Playlist\MovePlaylistSongsRequest;
use App\Models\Playlist;
use App\Services\PlaylistService;

class MovePlaylistSongsController extends Controller
{
    public function __invoke(MovePlaylistSongsRequest $request, Playlist $playlist, PlaylistService $service)
    {
        $this->authorize('collaborate', $playlist);

        $service->movePlayablesInPlaylist(
            $playlist,
            $request->songs,
            $request->target,
            Placement::from($request->placement),
        );

        return response()->noContent();
    }
}
