<?php

namespace App\Http\Controllers\API\Download;

use App\Models\Album;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AlbumController extends Controller
{
    /**
     * Download all songs in an album.
     *
     * @param Album $album
     *
     * @return BinaryFileResponse
     */
    public function show(Album $album)
    {
        return response()->download($this->downloadService->from($album));
    }
}
