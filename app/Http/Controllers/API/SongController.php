<?php

namespace App\Http\Controllers\API;

use App\Http\Streamers\PHPStreamer;
use App\Http\Streamers\XSendFileStreamer;
use App\Http\Streamers\XAccelRedirectStreamer;
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
        switch (env('STREAMING_METHOD')) {
            case 'x-sendfile':
                return (new XSendFileStreamer($id))->stream();
            case 'x-accel-redirect':
                return (new XAccelRedirectStreamer($id))->stream();
            default:
                return (new PHPStreamer($id))->stream();
        }
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
