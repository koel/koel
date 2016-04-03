<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\PlaylistStoreRequest;
use App\Models\Playlist;
use Illuminate\Http\Request;

class PlaylistController extends Controller
{
    /**
     * Gets all playlists by the current user.
     *
     * @return \Illuminate\Http\JsonResponse
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(PlaylistStoreRequest $request)
    {
        $playlist = auth()->user()->playlists()->create($request->only('name'));
        $playlist->songs()->sync($request->input('songs'));

        $playlist->songs = $playlist->songs->pluck('id');

        return response()->json($playlist);
    }

    /**
     * Rename a playlist.
     *
     * @param \Illuminate\Http\Request $request
     * @param Playlist                 $playlist
     *
     * @return \Illuminate\Http\JsonResponse
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
     * @param \Illuminate\Http\Request $request
     * @param Playlist                 $playlist
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sync(Request $request, Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        $playlist->songs()->sync($request->input('songs'));

        return response()->json();
    }

    /**
     * Delete a playlist.
     *
     * @param Playlist $playlist
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        $playlist->delete();

        return response()->json();
    }
}
