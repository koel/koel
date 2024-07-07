<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\MovePlaylistSongsRequest;
use App\Models\Playlist;
use App\Services\PlaylistService;

class MovePlaylistSongsController extends Controller
{
    public function __invoke(MovePlaylistSongsRequest $request, Playlist $playlist, PlaylistService $service)
    {
        $this->authorize('collaborate', $playlist);

        $service->movePlayablesInPlaylist($playlist, $request->songs, $request->target, $request->type);

        return response()->noContent();
    }
}
