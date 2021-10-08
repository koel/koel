<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\PlaylistStoreRequest;
use App\Http\Requests\API\PlaylistSyncRequest;
use App\Models\Playlist;
use App\Models\User;
use App\Repositories\PlaylistRepository;
use App\Services\PlaylistService;
use App\Services\SmartPlaylistService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

class PlaylistController extends Controller
{
    private PlaylistRepository $playlistRepository;
    private PlaylistService $playlistService;
    private SmartPlaylistService $smartPlaylistService;

    /** @var User */
    private ?Authenticatable $currentUser;

    public function __construct(
        PlaylistRepository $playlistRepository,
        PlaylistService $playlistService,
        SmartPlaylistService $smartPlaylistService,
        ?Authenticatable $currentUser
    ) {
        $this->playlistRepository = $playlistRepository;
        $this->playlistService = $playlistService;
        $this->smartPlaylistService = $smartPlaylistService;
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

    public function update(Request $request, Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        $playlist->update($request->only('name', 'rules'));

        return response()->json($playlist);
    }

    public function sync(PlaylistSyncRequest $request, Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        abort_if($playlist->is_smart, 403, 'A smart playlist\'s content cannot be updated manually.');

        $playlist->songs()->sync((array) $request->songs);

        return response()->json();
    }

    public function getSongs(Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        return response()->json(
            $playlist->is_smart
                ? $this->smartPlaylistService->getSongs($playlist)->pluck('id')
                : $playlist->songs->pluck('id')
        );
    }

    public function destroy(Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        $playlist->delete();

        return response()->json();
    }
}
