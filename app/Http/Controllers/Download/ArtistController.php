<?php

namespace App\Http\Controllers\Download;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Services\DownloadService;

class ArtistController extends Controller
{
    public function __construct(private DownloadService $downloadService)
    {
    }

    public function show(Artist $artist)
    {
        return response()->download($this->downloadService->from($artist));
    }
}
