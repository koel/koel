<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\PlaylistFolderPlaylistDestroyRequest;
use App\Http\Requests\API\PlaylistFolderPlaylistStoreRequest;
use App\Models\PlaylistFolder;
use App\Services\PlaylistFolderService;
use Illuminate\Support\Arr;

class PlaylistFolderPlaylistController extends Controller
{
    public function __construct(private PlaylistFolderService $service)
    {
    }

    public function store(PlaylistFolder $playlistFolder, PlaylistFolderPlaylistStoreRequest $request)
    {
        $this->authorize('own', $playlistFolder);

        $this->service->addPlaylistsToFolder($playlistFolder, Arr::wrap($request->playlists));

        return response()->noContent();
    }

    public function destroy(PlaylistFolder $playlistFolder, PlaylistFolderPlaylistDestroyRequest $request)
    {
        $this->authorize('own', $playlistFolder);

        $this->service->movePlaylistsToRootLevel(Arr::wrap($request->playlists));

        return response()->noContent();
    }
}
