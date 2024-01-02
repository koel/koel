<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlaylistFolderResource;
use App\Http\Resources\PlaylistResource;
use App\Http\Resources\QueueStateResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\SettingRepository;
use App\Repositories\SongRepository;
use App\Services\ApplicationInformationService;
use App\Services\ITunesService;
use App\Services\LastfmService;
use App\Services\QueueService;
use App\Services\SpotifyService;
use App\Services\YouTubeService;
use Illuminate\Contracts\Auth\Authenticatable;

class FetchInitialDataController extends Controller
{
    /** @param User $user */
    public function __invoke(
        ITunesService $iTunesService,
        SettingRepository $settingRepository,
        SongRepository $songRepository,
        ApplicationInformationService $applicationInformationService,
        QueueService $queueService,
        ?Authenticatable $user
    ) {
        return response()->json([
            'settings' => $user->is_admin ? $settingRepository->getAllAsKeyValueArray() : [],
            'playlists' => PlaylistResource::collection($user->playlists),
            'playlist_folders' => PlaylistFolderResource::collection($user->playlist_folders),
            'current_user' => UserResource::make($user, true),
            'use_last_fm' => LastfmService::used(),
            'use_spotify' => SpotifyService::enabled(),
            'use_you_tube' => YouTubeService::enabled(),
            'use_i_tunes' => $iTunesService->used(),
            'allow_download' => config('koel.download.allow'),
            'supports_transcoding' => config('koel.streaming.ffmpeg_path')
                && is_executable(config('koel.streaming.ffmpeg_path')),
            'cdn_url' => static_url(),
            'current_version' => koel_version(),
            'latest_version' => $user->is_admin
                ? $applicationInformationService->getLatestVersionNumber()
                : koel_version(),
            'song_count' => $songRepository->count(),
            'song_length' => $songRepository->getTotalLength(),
            'queue_state' => QueueStateResource::make($queueService->getQueueState($user)),
        ]);
    }
}
