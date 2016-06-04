<?php

namespace App\Http\Controllers\API\Download;

use App\Http\Requests\API\Download\SongRequest;
use App\Models\Song;
use Download;

class SongController extends Controller
{
    /**
     * Download a song or multiple songs.
     *
     * @param  SongRequest $request
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download(SongRequest $request)
    {
        $songs = Song::whereIn('id', $request->songs)->get();

        return response()->download(Download::from($songs));
    }
}
