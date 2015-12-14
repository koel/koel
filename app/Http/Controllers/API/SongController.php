<?php

namespace App\Http\Controllers\API;

use App\Http\Streamers\PHPStreamer;
use App\Http\Streamers\XSendFileStreamer;
use App\Models\Song;

class SongController extends Controller
{
    /**
     * Play a song.
     * As of current Koel supports two streamer: x_sendfile and native PHP readfile.
     *
     * @param $id
     */
    public function play($id)
    {
        if (env('MOD_X_SENDFILE_ENABLED')) {
            (new XSendFileStreamer($id))->stream();

            return;
        }

        (new PHPStreamer($id))->stream();

        // Exit here to avoid accidentally sending extra content at the end of the file.
        exit;
    }

    /**
     * Get the lyrics of a song.
     *
     * @param $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLyrics($id)
    {
        return response()->json(Song::findOrFail($id)->lyrics);
    }
}
