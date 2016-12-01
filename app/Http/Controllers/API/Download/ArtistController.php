<?php

namespace App\Http\Controllers\API\Download;

use App\Models\Artist;
use Download;

class ArtistController extends Controller
{
    /**
     * Download all songs by an artist.
     * Don't see why one would need this, really.
     * Let's pray to God the user doesn't trigger this on Elvis.
     *
     * @param Artist $artist
     *
     * @throws \Exception
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download(Artist $artist)
    {
        return response()->download(Download::from($artist));
    }
}
