<?php

namespace App\Http\Controllers\Download;

use App\Models\Album;

class AlbumController extends Controller
{
    public function show(Album $album)
    {
        return response()->download($this->downloadService->from($album));
    }
}
