<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlaylistFolderResource;
use App\Http\Resources\PlaylistResource;
use App\Http\Resources\UserResource;
use App\Models\User;
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
        private ITunesService $iTunesService,
        private SettingRepository $settingRepository,
        private SongRepository $songRepository,
        private ApplicationInformationService $applicationInformationService,
        private ?Authenticatable $user
    ) {
    }

    public function index()
    {
        return response()->json([
            'settings' => $this->user->is_admin ? $this->settingRepository->getAllAsKeyValueArray() : [],
            'playlists' => PlaylistResource::collection($this->user->playlists),
            'playlist_folders' => PlaylistFolderResource::collection($this->user->playlist_folders),
            'current_user' => UserResource::make($this->user, true),
            'use_last_fm' => LastfmService::used(),
            'use_you_tube' => YouTubeService::enabled(),
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
