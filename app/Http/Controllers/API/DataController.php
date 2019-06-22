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

/**
 * @group 2. Application data
 */
class DataController extends Controller
{
    private const RECENTLY_PLAYED_EXCERPT_COUNT = 7;

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
     * Get application data.
     *
     * The big fat call to retrieve a set of application data catered for the current user
     * (songs, albums, artists, playlists, interactions, and if the user is an admin, settings as well).
     * Naturally, this call should be made right after the user has been logged in, when you need to populate
     * the application's interface with useful information.
     *
     * @responseFile responses/data.index.json
     *
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        return response()->json($this->mediaCacheService->get() + [
            'settings' => $request->user()->is_admin ? $this->settingRepository->getAllAsKeyValueArray() : [],
            'playlists' => $this->playlistRepository->getAllByCurrentUser(),
            'interactions' => $this->interactionRepository->getAllByCurrentUser(),
            'recentlyPlayed' => $this->interactionRepository->getRecentlyPlayed(
                $request->user(),
                self::RECENTLY_PLAYED_EXCERPT_COUNT
            ),
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
