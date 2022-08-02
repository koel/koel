<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Services\DownloadService;

class AlbumController extends Controller
{
    public function __construct(private DownloadService $downloadService)
    {
    }

    public function show(Album $album)
    {
        return response()->download($this->downloadService->from($album));
    }
}
