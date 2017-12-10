<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\PlaylistStoreRequest;
use App\Http\Requests\API\PlaylistSyncRequest;
use App\Models\Playlist;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaylistController extends Controller
{
    /**
     * Gets all playlists by the current user.
     *
     * @return JsonResponse
     */
    public function index()
    {
        return response()->json(Playlist::byCurrentUser()->orderBy('name')->with('songs')->get());
    }

    /**
     * Create a new playlist.
     *
     * @param PlaylistStoreRequest $request
     *
     * @return JsonResponse
     */
    public function store(PlaylistStoreRequest $request)
    {
        $playlist = $request->user()->playlists()->create($request->only('name'));
        $playlist->songs()->sync((array) $request->songs);

        $playlist->songs = $playlist->songs->pluck('id');

        return response()->json($playlist);
    }

    /**
     * Rename a playlist.
     *
     * @param Request  $request
     * @param Playlist $playlist
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
     * Sync a playlist with songs.
     * Any songs that are not populated here will be removed from the playlist.
     *
     * @param PlaylistSyncRequest $request
     * @param Playlist            $playlist
     *
     * @throws AuthorizationException
     *
     * @return JsonResponse
     */
    public function sync(PlaylistSyncRequest $request, Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        $playlist->songs()->sync((array) $request->songs);

        return response()->json();
    }

    /**
     * Get a playlist's all songs.
     *
     * @param Playlist $playlist
     *
     * @throws AuthorizationException
     *
     * @return JsonResponse
     */
    public function getSongs(Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        return response()->json($playlist->songs->pluck('id'));
    }

    /**
     * Delete a playlist.
     *
     * @param Playlist $playlist
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
