<?php

namespace App\Http\Controllers\API\Download;

use App\Models\Album;
use Download;

class AlbumController extends Controller
{
    /**
     * Download all songs in an album.
     *
     * @param Album $album
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download(Album $album)
    {
        return response()->download(Download::from($album));
    }
}
