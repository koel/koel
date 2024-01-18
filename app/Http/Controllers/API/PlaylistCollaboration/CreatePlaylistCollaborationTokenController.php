<?php

namespace App\Http\Controllers\API\PlaylistCollaboration;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlaylistCollaborationTokenResource;
use App\Models\Playlist;
use App\Services\PlaylistCollaborationService;
use Illuminate\Contracts\Auth\Authenticatable;

class CreatePlaylistCollaborationTokenController extends Controller
{
    public function __invoke(
        Playlist $playlist,
        PlaylistCollaborationService $collaborationService,
        Authenticatable $user
    ) {
        $this->authorize('invite-collaborators', $playlist);

        return PlaylistCollaborationTokenResource::make($collaborationService->createToken($playlist));
    }
}
