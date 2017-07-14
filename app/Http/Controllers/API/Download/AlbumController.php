<?php

namespace App\Http\Controllers\API\Download;

use App\Models\Album;
use Download;
use Exception;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AlbumController extends Controller
{
    /**
     * Download all songs in an album.
     *
     * @param Album $album
     *
     * @throws Exception
     *
     * @return BinaryFileResponse
     */
    public function download(Album $album)
    {
        return response()->download(Download::from($album));
    }
}
