<?php

namespace App\Http\Controllers\API;

use App\Models\Artist;
use App\Models\Interaction;
use App\Models\Playlist;
use App\Models\Setting;
use App\Models\User;

class DataController extends Controller
{
    /**
     * Get a set of application data.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $playlists = Playlist::byCurrentUser()->orderBy('name')->with('songs')->get()->toArray();

        // We don't need full song data, just ID's
        foreach ($playlists as &$playlist) {
            $playlist['songs'] = array_pluck($playlist['songs'], 'id');
        }

        return response()->json([
            'artists' => Artist::orderBy('name')->with('albums', with('albums.songs'))->get(),
            'settings' => Setting::all()->lists('value', 'key'),
            'playlists' => $playlists,
            'interactions' => Interaction::byCurrentUser()->get(),
            'users' => auth()->user()->is_admin ? User::all() : [],
            'currentUser' => auth()->user(),
            'useLastfm' => env('LASTFM_API_KEY') && env('LASTFM_API_SECRET'),
        ]);
    }
}
