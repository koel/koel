<?php

namespace App\Http\Controllers\Download;

use App\Models\Artist;

class ArtistController extends Controller
{
    public function show(Artist $artist)
    {
        return response()->download($this->downloadService->from($artist));
    }
}
