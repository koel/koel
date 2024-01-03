<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Services\DownloadService;

class DownloadAlbumController extends Controller
{
    public function __invoke(Album $album, DownloadService $download)
    {
        return response()->download($download->from($album));
    }
}
