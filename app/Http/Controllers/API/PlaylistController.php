<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\PlaylistStoreRequest;
use App\Http\Requests\API\PlaylistSyncRequest;
use App\Models\Playlist;
use App\Models\User;
use App\Repositories\PlaylistRepository;
use App\Services\SmartPlaylistService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaylistController extends Controller
{
    private $playlistRepository;
    private $smartPlaylistService;

    /** @var User */
    private $currentUser;

    public function __construct(
        PlaylistRepository $playlistRepository,
        SmartPlaylistService $smartPlaylistService,
        ?Authenticatable $currentUser
    ) {
        $this->playlistRepository = $playlistRepository;
        $this->smartPlaylistService = $smartPlaylistService;
        $this->currentUser = $currentUser;
    }

    public function index()
    {
        return response()->json($this->playlistRepository->getAllByCurrentUser());
    }

    public function store(PlaylistStoreRequest $request)
    {
        /** @var Playlist $playlist */
        $playlist = $this->currentUser->playlists()->create([
            'name' => $request->name,
            'rules' => $request->rules,
        ]);

        $songs = (array) $request->songs;

        if ($songs) {
            $playlist->songs()->sync($songs);
        }

        $playlist->songs = $playlist->songs->pluck('id');

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

    /**
     * Delete a playlist
     *
     * @response []
     *
     * @throws Exception
     * @throws AuthorizationException
     *
     * @return JsonResponse
     */
    public function destroy(Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        $playlist->delete();

        return response()->json();
    }
}
