<?php

namespace App\Http\Controllers\V6\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\V6\API\AddSongsToPlaylistRequest;
use App\Http\Requests\V6\API\RemoveSongsFromPlaylistRequest;
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
        $this->authorize('own', $playlist);

        return SongResource::collection(
            $playlist->is_smart
                ? $this->smartPlaylistService->getSongs($playlist, $this->user)
                : $this->songRepository->getByStandardPlaylist($playlist, $this->user)
        );
    }

    public function store(Playlist $playlist, AddSongsToPlaylistRequest $request)
    {
        $this->authorize('own', $playlist);

        abort_if($playlist->is_smart, Response::HTTP_FORBIDDEN);

        $this->playlistService->addSongsToPlaylist($playlist, $request->songs);

        return response()->noContent();
    }

    public function destroy(Playlist $playlist, RemoveSongsFromPlaylistRequest $request)
    {
        $this->authorize('own', $playlist);

        abort_if($playlist->is_smart, Response::HTTP_FORBIDDEN);

        $this->playlistService->removeSongsFromPlaylist($playlist, $request->songs);

        return response()->noContent();
    }
}
