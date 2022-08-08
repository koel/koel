<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\InteractionRepository;
use App\Repositories\PlaylistRepository;
use App\Repositories\SettingRepository;
use App\Repositories\UserRepository;
use App\Services\ApplicationInformationService;
use App\Services\ITunesService;
use App\Services\LastfmService;
use App\Services\MediaCacheService;
use App\Services\YouTubeService;
use Illuminate\Contracts\Auth\Authenticatable;

class DataController extends Controller
{
    private const RECENTLY_PLAYED_EXCERPT_COUNT = 7;

    /** @param User $currentUser */
    public function __construct(
        private MediaCacheService $mediaCacheService,
        private SettingRepository $settingRepository,
        private PlaylistRepository $playlistRepository,
        private InteractionRepository $interactionRepository,
        private UserRepository $userRepository,
        private ApplicationInformationService $applicationInformationService,
        private ?Authenticatable $currentUser
    ) {
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
            'useLastfm' => LastfmService::used(),
            'useYouTube' => YouTubeService::enabled(),
            'useiTunes' => ITunesService::used(),
            'allowDownload' => config('koel.download.allow'),
            'supportsTranscoding' => config('koel.streaming.ffmpeg_path')
                && is_executable(config('koel.streaming.ffmpeg_path')),
            'cdnUrl' => static_url(),
            'currentVersion' => koel_version(),
            'latestVersion' => $this->currentUser->is_admin
                ? $this->applicationInformationService->getLatestVersionNumber()
                : koel_version(),
        ]);
    }
}
