<?php

namespace App\Http\Controllers\API\Download;

use App\Http\Requests\API\Download\Request;
use App\Models\Album;
use Download;

class AlbumController extends Controller
{
    /**
     * Download all songs in an album.
     *
     * @param Request $request
     * @param Album   $album
     *
     * @return
     */
    public function download(Request $request, Album $album)
    {
        return response()->download(Download::from($album));
    }
}
