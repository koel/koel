<?php

namespace App\Http\Controllers\API\Download;

use App\Http\Requests\API\Download\Request;
use App\Models\Artist;
use Download;

class ArtistController extends Controller
{
    /**
     * Download all songs by an artist.
     * Don't see why one would need this, really.
     * Let's pray to God the user doesn't trigger this on Elvis.
     *
     * @param Request $request
     * @param Artist  $artist
     *
     * @return
     */
    public function download(Request $request, Artist $artist)
    {
        return response()->download(Download::from($artist));
    }
}
