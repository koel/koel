<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlaylistFolderResource;
use App\Http\Resources\PlaylistResource;
use App\Http\Resources\QueueStateResource;
use App\Http\Resources\UserResource;
use App\Models\Setting;
use App\Models\User;
use App\Repositories\PlaylistRepository;
use App\Repositories\SettingRepository;
use App\Repositories\SongRepository;
use App\Services\ApplicationInformationService;
use App\Services\ITunesService;
use App\Services\LastfmService;
use App\Services\License\Contracts\LicenseServiceInterface;
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
        PlaylistRepository $playlistRepository,
        ApplicationInformationService $applicationInformationService,
        QueueService $queueService,
        LicenseServiceInterface $licenseService,
        ?Authenticatable $user
    ) {
        $licenseStatus = $licenseService->getStatus();

        return response()->json([
            'settings' => $user->is_admin ? $settingRepository->getAllAsKeyValueArray() : [],
            'playlists' => PlaylistResource::collection($playlistRepository->getAllAccessibleByUser($user)),
            'playlist_folders' => PlaylistFolderResource::collection($user->playlist_folders),
            'current_user' => UserResource::make($user, true),
            'uses_last_fm' => LastfmService::used(),
            'uses_spotify' => SpotifyService::enabled(),
            'uses_you_tube' => YouTubeService::enabled(),
            'uses_i_tunes' => $iTunesService->used(),
            'allows_download' => config('koel.download.allow'),
            'supports_batch_downloading' => extension_loaded('zip'),
            'media_path_set' => (bool) Setting::get('media_path'),
            'supports_transcoding' => config('koel.streaming.ffmpeg_path')
                && is_executable(config('koel.streaming.ffmpeg_path')),
            'cdn_url' => static_url(),
            'current_version' => koel_version(),
            'latest_version' => $user->is_admin
                ? $applicationInformationService->getLatestVersionNumber()
                : koel_version(),
            'song_count' => $songRepository->countSongs(),
            'song_length' => $songRepository->getTotalSongLength(),
            'queue_state' => QueueStateResource::make($queueService->getQueueState($user)),
            'koel_plus' => [
                'active' => $licenseStatus->isValid(),
                'short_key' => $licenseStatus->license?->short_key,
                'customer_name' => $licenseStatus->license?->meta->customerName,
                'customer_email' => $licenseStatus->license?->meta->customerEmail,
                'product_id' => config('lemonsqueezy.product_id'),
            ],
            'storage_driver' => config('koel.storage_driver'),
        ]);
    }
}
