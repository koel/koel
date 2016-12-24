<?php

namespace App\Http\Controllers\API;

use App\Application;
use App\Models\Artist;
use App\Models\Interaction;
use App\Models\Playlist;
use App\Models\Setting;
use App\Models\User;
use iTunes;
use Lastfm;
use YouTube;

class DataController extends Controller {

    /**
     * Get a set of application data.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index() {
        if (env('CACHE_DRIVER') == 'apc' && apc_exists(auth()->user()->id . '_load')) {
            return apc_fetch(auth()->user()->id . '_load');
        } else {
            $playlists = Playlist::byCurrentUser()->orderBy('name')->with('songs')->get()->toArray();

            // We don't need full song data, just ID's
            foreach ($playlists as &$playlist) {
                $playlist['songs'] = array_pluck($playlist['songs'], 'id');
            }

            $response = response()->json([
                'artists' => Artist::orderBy('name')->with('albums', with('albums.songs'))->get(),
                'settings' => auth()->user()->is_admin ? Setting::pluck('value', 'key')->all() : [],
                'playlists' => $playlists,
                'interactions' => Interaction::byCurrentUser()->get(),
                'users' => auth()->user()->is_admin ? User::all() : [],
                'currentUser' => auth()->user(),
                'useLastfm' => Lastfm::used(),
                'useYouTube' => YouTube::enabled(),
                'useiTunes' => iTunes::used(),
                'allowDownload' => config('koel.download.allow'),
                'cdnUrl' => app()->staticUrl(),
                'currentVersion' => Application::KOEL_VERSION,
                'latestVersion' => auth()->user()->is_admin ? app()->getLatestVersion() : Application::KOEL_VERSION,
            ]);

            if (env('CACHE_DRIVER') == 'apc') {
                apc_store(auth()->user()->id . '_load', $response, 86400);
            }
            return $response;
        }
    }

}
