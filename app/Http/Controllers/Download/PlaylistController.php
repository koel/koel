<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\Playlist;
use App\Services\DownloadService;

class PlaylistController extends Controller
{
    public function __construct(private DownloadService $downloadService)
    {
    }

    public function show(Playlist $playlist)
    {
        $this->authorize('owner', $playlist);

        return response()->download($this->downloadService->from($playlist));
    }
}
