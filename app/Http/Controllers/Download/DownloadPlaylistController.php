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

        $songs = $repository->getByPlaylist($playlist, $user);

        abort_if($songs->isEmpty(), 404, 'The playlist is empty.');

        return $download->getDownloadable($songs)?->toResponse();
    }
}
