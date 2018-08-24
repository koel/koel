<?php

namespace App\Http\Controllers\API\Download;

use App\Models\Album;

class AlbumController extends Controller
{
    /**
     * Download all songs in an album.
     */
    public function show(Album $album)
    {
        return response()->download($this->downloadService->from($album));
    }
}
