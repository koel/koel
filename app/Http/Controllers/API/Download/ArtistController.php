<?php

namespace App\Http\Controllers\API\Download;

use App\Models\Artist;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ArtistController extends Controller
{
    /**
     * Download all songs by an artist.
     * Don't see why one would need this, really.
     * Let's pray to God the user doesn't trigger this on Elvis.
     *
     * @param Artist $artist
     *
     * @return BinaryFileResponse
     */
    public function show(Artist $artist)
    {
        return response()->download($this->downloadService->from($artist));
    }
}
