<?php

namespace App\Http\Controllers\Download;

use App\Models\Album;

/**
 * @group 6. Download
 */
class AlbumController extends Controller
{
    /**
     * Download a whole album
     *
     * @response []
     */
    public function show(Album $album)
    {
        return response()->download($this->downloadService->from($album));
    }
}
