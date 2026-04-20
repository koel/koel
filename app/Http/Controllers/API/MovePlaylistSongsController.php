<?php

namespace App\Http\Controllers\API;

use App\Enums\Placement;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\Playlist\MovePlaylistSongsRequest;
use App\Models\Playlist;
use App\Services\PlaylistService;
use Illuminate\Http\Response;

class MovePlaylistSongsController extends Controller
{
    public function __invoke(MovePlaylistSongsRequest $request, Playlist $playlist, PlaylistService $service)
    {
        $this->authorize('collaborate', $playlist);
        abort_if($playlist->is_locked, Response::HTTP_FORBIDDEN, 'Editing is disabled for this playlist.');

        $service->movePlayablesInPlaylist(
            $playlist,
            $request->songs,
            $request->target,
            Placement::from($request->placement),
        );

        return response()->noContent();
    }
}
