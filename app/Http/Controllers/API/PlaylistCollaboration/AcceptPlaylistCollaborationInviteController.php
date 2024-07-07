<?php

namespace App\Http\Controllers\API\PlaylistCollaboration;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlaylistResource;
use App\Models\User;
use App\Services\PlaylistCollaborationService;
use Illuminate\Contracts\Auth\Authenticatable;

class AcceptPlaylistCollaborationInviteController extends Controller
{
    /** @param User $user */
    public function __invoke(PlaylistCollaborationService $service, Authenticatable $user)
    {
        $playlist = $service->acceptUsingToken(request()->input('token'), $user);

        return PlaylistResource::make($playlist);
    }
}
