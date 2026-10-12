<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\PlaylistFolder\PlaylistFolderPlaylistRequest;
use App\Models\PlaylistFolder;
use App\Services\Playlist\PlaylistFolderService;
use Illuminate\Support\Arr;

class PlaylistFolderPlaylistController extends Controller
{
    public function __construct(
        private readonly PlaylistFolderService $service,
    ) {}

    public function store(PlaylistFolder $playlistFolder, PlaylistFolderPlaylistRequest $request)
    {
        $this->authorize('own', $playlistFolder);

        $this->service->addPlaylistsToFolder($playlistFolder, Arr::wrap($request->playlists));

        return response()->noContent();
    }

    public function destroy(PlaylistFolder $playlistFolder, PlaylistFolderPlaylistRequest $request)
    {
        $this->authorize('own', $playlistFolder);

        $this->service->movePlaylistsToRootLevel($playlistFolder, Arr::wrap($request->playlists));

        return response()->noContent();
    }
}
