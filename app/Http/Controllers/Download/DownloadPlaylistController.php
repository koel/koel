<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\Playlist;
use App\Services\DownloadService;

class DownloadPlaylistController extends Controller
{
    public function __invoke(Playlist $playlist, DownloadService $download)
    {
        $this->authorize('own', $playlist);

        return response()->download($download->from($playlist));
    }
}
