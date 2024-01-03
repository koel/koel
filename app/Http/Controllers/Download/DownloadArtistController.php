<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Services\DownloadService;

class DownloadArtistController extends Controller
{
    public function __invoke(Artist $artist, DownloadService $download)
    {
        return response()->download($download->from($artist));
    }
}
