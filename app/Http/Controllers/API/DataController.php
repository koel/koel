<?php

namespace App\Http\Controllers\API;

use App\Application;
use App\Models\User;
use App\Repositories\InteractionRepository;
use App\Repositories\PlaylistRepository;
use App\Repositories\SettingRepository;
use App\Repositories\UserRepository;
use App\Services\ApplicationInformationService;
use App\Services\iTunesService;
use App\Services\LastfmService;
use App\Services\MediaCacheService;
use App\Services\YouTubeService;
use Illuminate\Contracts\Auth\Authenticatable;

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

    /** @var User */
    private $currentUser;

    public function __construct(
        LastfmService $lastfmService,
        YouTubeService $youTubeService,
        iTunesService $iTunesService,
        MediaCacheService $mediaCacheService,
        SettingRepository $settingRepository,
        PlaylistRepository $playlistRepository,
        InteractionRepository $interactionRepository,
        UserRepository $userRepository,
        ApplicationInformationService $applicationInformationService,
        Authenticatable $currentUser
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
        $this->currentUser = $currentUser;
    }

    public function index()
    {
        return response()->json($this->mediaCacheService->get() + [
            'settings' => $this->currentUser->is_admin ? $this->settingRepository->getAllAsKeyValueArray() : [],
            'playlists' => $this->playlistRepository->getAllByCurrentUser(),
            'interactions' => $this->interactionRepository->getAllByCurrentUser(),
            'recentlyPlayed' => $this->interactionRepository->getRecentlyPlayed(
                $this->currentUser,
                self::RECENTLY_PLAYED_EXCERPT_COUNT
            ),
            'users' => $this->currentUser->is_admin ? $this->userRepository->getAll() : [],
            'currentUser' => $this->currentUser,
            'useLastfm' => $this->lastfmService->used(),
            'useYouTube' => $this->youTubeService->enabled(),
            'useiTunes' => $this->iTunesService->used(),
            'allowDownload' => config('koel.download.allow'),
            'supportsTranscoding' => config('koel.streaming.ffmpeg_path')
                && is_executable(config('koel.streaming.ffmpeg_path')),
            'cdnUrl' => app()->staticUrl(),
            'currentVersion' => Application::KOEL_VERSION,
            'latestVersion' => $this->currentUser->is_admin
                ? $this->applicationInformationService->getLatestVersionNumber()
                : Application::KOEL_VERSION,
        ]);
    }
}
