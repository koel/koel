<?php

namespace App\Http\Controllers\API;

use App\Application;
use App\Models\Interaction;
use App\Models\Playlist;
use App\Models\Setting;
use App\Models\User;
use App\Services\iTunesService;
use App\Services\LastfmService;
use App\Services\YouTubeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use iTunes;
use Lastfm;
use MediaCache;
use YouTube;

class DataController extends Controller
{
    private $lastfmService;
    private $youTubeService;
    private $iTunesService;

    public function __construct(
        LastfmService $lastfmService,
        YouTubeService $youTubeService,
        iTunesService $iTunesService
    )
    {
        $this->lastfmService = $lastfmService;
        $this->youTubeService = $youTubeService;
        $this->iTunesService = $iTunesService;
    }

    /**
     * Get a set of application data.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        return response()->json(MediaCache::get() + [
            'settings' => $request->user()->is_admin ? Setting::pluck('value', 'key')->all() : [],
            'playlists' => Playlist::byCurrentUser()->orderBy('name')->get()->toArray(),
            'interactions' => Interaction::byCurrentUser()->get(),
            'users' => $request->user()->is_admin ? User::all() : [],
            'currentUser' => $request->user(),
            'useLastfm' => $this->lastfmService->used(),
            'useYouTube' => $this->youTubeService->enabled(),
            'useiTunes' => $this->iTunesService->used(),
            'allowDownload' =>  config('koel.download.allow'),
            'supportsTranscoding' => config('koel.streaming.ffmpeg_path')
                && is_executable(config('koel.streaming.ffmpeg_path')),
            'cdnUrl' => app()->staticUrl(),
            'currentVersion' => Application::KOEL_VERSION,
            'latestVersion' => $request->user()->is_admin ? app()->getLatestVersion() : Application::KOEL_VERSION,
        ]);
    }
}
