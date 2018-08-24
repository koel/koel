<?php

namespace App\Http\Controllers\API\Download;

use App\Models\Artist;

class ArtistController extends Controller
{
    /**
     * Download all songs by an artist.
     * Don't see why one would need this, really.
     * Let's pray to God the user doesn't trigger this on Elvis.
     */
    public function show(Artist $artist)
    {
        return response()->download($this->downloadService->from($artist));
    }
}
