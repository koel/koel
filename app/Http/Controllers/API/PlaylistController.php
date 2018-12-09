<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\PlaylistStoreRequest;
use App\Http\Requests\API\PlaylistSyncRequest;
use App\Models\Playlist;
use App\Repositories\PlaylistRepository;
use App\Services\SmartPlaylistService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group 4. Playlist management
 */
class PlaylistController extends Controller
{
    private $playlistRepository;
    private $smartPlaylistService;

    public function __construct(PlaylistRepository $playlistRepository, SmartPlaylistService $smartPlaylistService)
    {
        $this->playlistRepository = $playlistRepository;
        $this->smartPlaylistService = $smartPlaylistService;
    }

    /**
     * Get current user's playlists.
     *
     * @responseFile responses/playlist.index.json
     *
     * @return JsonResponse
     */
    public function index()
    {
        return response()->json($this->playlistRepository->getAllByCurrentUser());
    }

    /**
     * Create a new playlist.
     *
     * @bodyParam name string required Name of the playlist. Example: Sleepy Songs
     * @bodyParam rules array An array of rules if creating a "smart playlist."
     * @responseFile responses/playlist.store.json
     *
     * @return JsonResponse
     */
    public function store(PlaylistStoreRequest $request)
    {
        /** @var Playlist $playlist */
        $playlist = $request->user()->playlists()->create([
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

    /**
     * Rename a playlist.
     *
     * @bodyParam name string required New name of the playlist. Example: Catchy Songs
     * @responseFile responses/playlist.update.json
     *
     * @throws AuthorizationException
     *
     * @return JsonResponse
     */
    public function update(Request $request, Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        $playlist->update($request->only('name'));

        return response()->json($playlist);
    }

    /**
     * Replace a playlist's content.
     *
     * Instead of adding or removing songs individually, a playlist's content is replaced entirely with an array of song IDs.
     *
     * @bodyParam songs array required An array of song IDs.
     * @response []
     *
     * @throws AuthorizationException
     *
     * @return JsonResponse
     */
    public function sync(PlaylistSyncRequest $request, Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        abort_if($playlist->is_smart, 403, 'A smart playlist\'s content cannot be updated manually.');

        $playlist->songs()->sync((array) $request->songs);

        return response()->json();
    }

    /**
     * Get a playlist's songs.
     *
     * @response ["0146d01afb742b01f28ab8b556f9a75d", "c741133cb8d1982a5c60b1ce2a1e6e47", "..."]
     *
     * @throws AuthorizationException
     *
     * @return JsonResponse
     */
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
     * Delete a playlist.
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
