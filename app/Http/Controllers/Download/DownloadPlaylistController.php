<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\Playlist;
use App\Models\User;
use App\Repositories\SongRepository;
use App\Services\DownloadService;
use App\Services\SmartPlaylistService;
use Illuminate\Contracts\Auth\Authenticatable;

class DownloadPlaylistController extends Controller
{
    /** @param User $user */
    public function __invoke(
        Playlist $playlist,
        SongRepository $repository,
        SmartPlaylistService $smartPlaylistService,
        DownloadService $download,
        Authenticatable $user
    ) {
        $this->authorize('download', $playlist);

        return response()->download(
            $download->getDownloadablePath(
                $playlist->is_smart
                    ? $smartPlaylistService->getSongs($playlist, $user)
                    : $repository->getByStandardPlaylist($playlist, $user)
            )
        );
    }
}
