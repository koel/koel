<?php

namespace App\Http\Controllers\API;

use App\Application;
use App\Repositories\InteractionRepository;
use App\Repositories\PlaylistRepository;
use App\Repositories\SettingRepository;
use App\Repositories\UserRepository;
use App\Services\ApplicationInformationService;
use App\Services\iTunesService;
use App\Services\LastfmService;
use App\Services\MediaCacheService;
use App\Services\YouTubeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DataController extends Controller
{
    private $lastfmService;
    private $youTubeService;
    private $iTunesService;
    private $mediaCacheService;
    private $settingRepository;
    private $playlistRepository;
    private $interactionRepository;
    private $userRepository;
    private $applicationInformationService;

    public function __construct(
        LastfmService $lastfmService,
        YouTubeService $youTubeService,
        iTunesService $iTunesService,
        MediaCacheService $mediaCacheService,
        SettingRepository $settingRepository,
        PlaylistRepository $playlistRepository,
        InteractionRepository $interactionRepository,
        UserRepository $userRepository,
        ApplicationInformationService $applicationInformationService
    ) {
        $this->lastfmService = $lastfmService;
        $this->youTubeService = $youTubeService;
        $this->iTunesService = $iTunesService;
        $this->mediaCacheService = $mediaCacheService;
        $this->settingRepository = $settingRepository;
        $this->playlistRepository = $playlistRepository;
        $this->interactionRepository = $interactionRepository;
        $this->userRepository = $userRepository;
        $this->applicationInformationService = $applicationInformationService;
    }

    /**
     * Get a set of application data.
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        return response()->json($this->mediaCacheService->get() + [
            'settings' => $request->user()->is_admin ? $this->settingRepository->getAllAsKeyValueArray() : [],
            'playlists' => $this->playlistRepository->getAllByCurrentUser(),
            'interactions' => $this->interactionRepository->getAllByCurrentUser(),
            'users' => $request->user()->is_admin ? $this->userRepository->getAll() : [],
            'currentUser' => $request->user(),
            'useLastfm' => $this->lastfmService->used(),
            'useYouTube' => $this->youTubeService->enabled(),
            'useiTunes' => $this->iTunesService->used(),
            'allowDownload' =>  config('koel.download.allow'),
            'supportsTranscoding' => config('koel.streaming.ffmpeg_path')
                && is_executable(config('koel.streaming.ffmpeg_path')),
            'cdnUrl' => app()->staticUrl(),
            'currentVersion' => Application::KOEL_VERSION,
            'latestVersion' => $request->user()->is_admin
                ? $this->applicationInformationService->getLatestVersionNumber()
                : Application::KOEL_VERSION,
        ]);
    }
}
