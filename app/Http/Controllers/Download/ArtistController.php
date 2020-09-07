<?php

namespace App\Http\Controllers\Download;

use App\Models\Artist;

/**
 * @group 6. Download
 */
class ArtistController extends Controller
{
    /**
     * Download all songs by an artist
     *
     * Don't see why one would need this, really.
     * Let's pray to God the user doesn't trigger this on Elvis.
     *
     * @response []
     */
    public function show(Artist $artist)
    {
        return response()->download($this->downloadService->from($artist));
    }
}
