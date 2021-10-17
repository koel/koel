<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\PlaylistStoreRequest;
use App\Http\Requests\API\PlaylistUpdateRequest;
use App\Models\Playlist;
use App\Models\User;
use App\Repositories\PlaylistRepository;
use App\Services\PlaylistService;
use Illuminate\Contracts\Auth\Authenticatable;

class PlaylistController extends Controller
{
    private PlaylistRepository $playlistRepository;
    private PlaylistService $playlistService;

    /** @var User */
    private ?Authenticatable $currentUser;

    public function __construct(
        PlaylistRepository $playlistRepository,
        PlaylistService $playlistService,
        ?Authenticatable $currentUser
    ) {
        $this->playlistRepository = $playlistRepository;
        $this->playlistService = $playlistService;
        $this->currentUser = $currentUser;
    }

    public function index()
    {
        return response()->json($this->playlistRepository->getAllByCurrentUser());
    }

    public function store(PlaylistStoreRequest $request)
    {
        $playlist = $this->playlistService->createPlaylist(
            $request->name,
            $this->currentUser,
            (array) $request->songs,
            $request->rules
        );

        $playlist->songs = $playlist->songs->pluck('id')->toArray();

        return response()->json($playlist);
    }

    public function update(PlaylistUpdateRequest $request, Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        $playlist->update($request->only('name', 'rules'));

        return response()->json($playlist);
    }

    public function destroy(Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        $playlist->delete();

        return response()->json();
    }
}
