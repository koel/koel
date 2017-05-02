<?php

namespace App\Http\Controllers\API;

use App\Application;
use App\Models\Interaction;
use App\Models\Playlist;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use iTunes;
use Lastfm;
use MediaCache;
use YouTube;

class DataController extends Controller
{
    /**
     * Get a set of application data.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $playlists = Playlist::byCurrentUser()->orderBy('name')->with('songs')->get()->toArray();

        // We don't need full song data, just ID's
        foreach ($playlists as &$playlist) {
            $playlist['songs'] = array_pluck($playlist['songs'], 'id');
        }

        return response()->json(MediaCache::get() + [
            'settings' => $request->user()->is_admin ? Setting::pluck('value', 'key')->all() : [],
            'playlists' => $playlists,
            'interactions' => Interaction::byCurrentUser()->get(),
            'users' => $request->user()->is_admin ? User::all() : [],
            'currentUser' => $request->user(),
            'useLastfm' => Lastfm::used(),
            'useYouTube' => YouTube::enabled(),
            'useiTunes' => iTunes::used(),
            'allowDownload' =>  config('koel.download.allow'),
            'supportsTranscoding' => config('koel.streaming.ffmpeg_path') && is_executable(config('koel.streaming.ffmpeg_path')),
            'cdnUrl' => app()->staticUrl(),
            'currentVersion' => Application::KOEL_VERSION,
            'latestVersion' => $request->user()->is_admin ? app()->getLatestVersion() : Application::KOEL_VERSION,
        ]);
    }
}
