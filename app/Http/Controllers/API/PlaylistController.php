<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\PlaylistStoreRequest;
use App\Models\Playlist;
use Illuminate\Http\Request;

class PlaylistController extends Controller
{
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

        $playlist->songs = $playlist->songs->fetch('id');

        return response()->json($playlist);
    }

    /**
     * Rename a playlist.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $playlist = Playlist::findOrFail($id);

        if ($playlist->user_id !== auth()->user()->id) {
            abort(403);
        }

        $playlist->name = $request->input('name');
        $playlist->save();

        return response()->json($playlist);
    }

    /**
     * Sync a playlist with songs.
     * Any songs that are not populated here will be removed from the playlist.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sync(Request $request, $id)
    {
        $playlist = Playlist::findOrFail($id);

        if ($playlist->user_id !== auth()->user()->id) {
            abort(403);
        }

        $playlist->songs()->sync($request->input('songs'));

        return response()->json();
    }

    /**
     * Delete a playlist.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        // This can't be put into a Request authorize(), due to Laravel(?)'s limitation.
        if (Playlist::findOrFail($id)->user_id !== auth()->user()->id) {
            abort(403);
        }

        Playlist::destroy($id);

        return response()->json();
    }
}
