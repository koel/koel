<?php

namespace App\Http\Controllers\V6\API;

use App\Http\Controllers\API\Controller;
use App\Http\Resources\SongResource;
use App\Models\Playlist;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\SmartPlaylistService;
use Illuminate\Contracts\Auth\Authenticatable;

class PlaylistSongController extends Controller
{
    /** @param User $user */
    public function __construct(
        private SongRepository $songRepository,
        private SmartPlaylistService $smartPlaylistService,
        private ?Authenticatable $user
    ) {
    }

    public function index(Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        return SongResource::collection(
            $playlist->is_smart
                ? $this->smartPlaylistService->getSongs($playlist)
                : $this->songRepository->getByStandardPlaylist($playlist, $this->user)
        );
    }
}
