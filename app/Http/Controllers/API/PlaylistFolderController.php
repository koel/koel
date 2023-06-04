<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\PlaylistFolderStoreRequest;
use App\Http\Requests\API\PlaylistFolderUpdateRequest;
use App\Http\Resources\PlaylistFolderResource;
use App\Models\PlaylistFolder;
use App\Models\User;
use App\Services\PlaylistFolderService;
use Illuminate\Contracts\Auth\Authenticatable;

class PlaylistFolderController extends Controller
{
    /** @param User $user */
    public function __construct(private PlaylistFolderService $service, private ?Authenticatable $user)
    {
    }

    public function store(PlaylistFolderStoreRequest $request)
    {
        return PlaylistFolderResource::make($this->service->createFolder($this->user, $request->name));
    }

    public function update(PlaylistFolder $playlistFolder, PlaylistFolderUpdateRequest $request)
    {
        $this->authorize('own', $playlistFolder);

        return PlaylistFolderResource::make($this->service->renameFolder($playlistFolder, $request->name));
    }

    public function destroy(PlaylistFolder $playlistFolder)
    {
        $this->authorize('own', $playlistFolder);

        $playlistFolder->delete();

        return response()->noContent();
    }
}
