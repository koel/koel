<?php

namespace App\Http\Controllers\V6\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\V6\Requests\AddSongsToPlaylistRequest;
use App\Http\Controllers\V6\Requests\RemoveSongsFromPlaylistRequest;
use App\Http\Resources\SongResource;
use App\Models\Playlist;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\PlaylistService;
use App\Services\SmartPlaylistService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Response;

class PlaylistSongController extends Controller
{
    /** @param User $user */
    public function __construct(
        private SongRepository $songRepository,
        private PlaylistService $playlistService,
        private SmartPlaylistService $smartPlaylistService,
        private ?Authenticatable $user
    ) {
    }

    public function index(Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        return SongResource::collection(
            $playlist->is_smart
                ? $this->smartPlaylistService->getSongs($playlist, $this->user)
                : $this->songRepository->getByStandardPlaylist($playlist, $this->user)
        );
    }

    public function add(Playlist $playlist, AddSongsToPlaylistRequest $request)
    {
        $this->authorize('owner', $playlist);

        abort_if($playlist->is_smart, Response::HTTP_FORBIDDEN);

        $this->playlistService->addSongsToPlaylist($playlist, $request->songs);

        return response()->noContent();
    }

    public function remove(Playlist $playlist, RemoveSongsFromPlaylistRequest $request)
    {
        $this->authorize('owner', $playlist);

        abort_if($playlist->is_smart, Response::HTTP_FORBIDDEN);

        $this->playlistService->removeSongsFromPlaylist($playlist, $request->songs);

        return response()->noContent();
    }
}
