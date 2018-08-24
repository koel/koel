<?php

namespace App\Http\Controllers\API\Download;

use App\Http\Requests\API\Download\SongRequest;
use App\Models\Song;

class SongController extends Controller
{
    /**
     * Download a song or multiple songs.
     */
    public function show(SongRequest $request)
    {
        $songs = Song::whereIn('id', $request->songs)->get();

        return response()->download($this->downloadService->from($songs));
    }
}
