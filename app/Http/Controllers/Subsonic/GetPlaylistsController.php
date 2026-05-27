<?php

namespace App\Http\Controllers\Subsonic;

use App\Http\Controllers\Controller;
use App\Http\Responses\Subsonic\Resources\PlaylistResource;
use App\Http\Responses\Subsonic\SubsonicResponse;
use App\Models\User;
use App\Repositories\PlaylistRepository;
use Illuminate\Contracts\Auth\Authenticatable;

class GetPlaylistsController extends Controller
{
    public function __construct(
        private readonly PlaylistRepository $playlistRepository,
    ) {}

    /** @param User $user */
    public function __invoke(Authenticatable $user)
    {
        $playlists = $this->playlistRepository
            ->getAllAccessibleByUser($user)
            ->loadCount('playables')
            ->loadSum('playables', 'length');

        return SubsonicResponse::ok([
            'playlists' => [
                'playlist' => $playlists->map(PlaylistResource::toArray(...))->all(),
            ],
        ]);
    }
}
