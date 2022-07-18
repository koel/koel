<?php

namespace App\Http\Controllers\V6\API;

use App\Http\Controllers\API\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\PlaylistRepository;
use App\Repositories\SettingRepository;
use App\Repositories\SongRepository;
use App\Services\ApplicationInformationService;
use App\Services\ITunesService;
use App\Services\LastfmService;
use App\Services\YouTubeService;
use Illuminate\Contracts\Auth\Authenticatable;

class DataController extends Controller
{
    /** @param User $user */
    public function __construct(
        private LastfmService $lastfmService,
        private YouTubeService $youTubeService,
        private ITunesService $iTunesService,
        private SettingRepository $settingRepository,
        private PlaylistRepository $playlistRepository,
        private SongRepository $songRepository,
        private ApplicationInformationService $applicationInformationService,
        private ?Authenticatable $user
    ) {
    }

    public function index()
    {
        return response()->json([
            'settings' => $this->user->is_admin ? $this->settingRepository->getAllAsKeyValueArray() : [],
            'playlists' => $this->playlistRepository->getAllByCurrentUser(),
            'current_user' => UserResource::make($this->user),
            'use_last_fm' => $this->lastfmService->used(),
            'use_you_tube' => $this->youTubeService->enabled(), // @todo clean this mess up
            'use_i_tunes' => $this->iTunesService->used(),
            'allow_download' => config('koel.download.allow'),
            'supports_transcoding' => config('koel.streaming.ffmpeg_path')
                && is_executable(config('koel.streaming.ffmpeg_path')),
            'cdn_url' => static_url(),
            'current_version' => koel_version(),
            'latest_version' => $this->user->is_admin
                ? $this->applicationInformationService->getLatestVersionNumber()
                : koel_version(),
            'song_count' => $this->songRepository->count(),
            'song_length' => $this->songRepository->getTotalLength(),
        ]);
    }
}
