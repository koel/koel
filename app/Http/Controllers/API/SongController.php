<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\SongUpdateRequest;
use App\Http\Streamers\PHPStreamer;
use App\Http\Streamers\TranscodingStreamer;
use App\Http\Streamers\XAccelRedirectStreamer;
use App\Http\Streamers\XSendFileStreamer;
use App\Models\Song;

class SongController extends Controller
{
    /**
     * Play a song.
     *
     * @link https://github.com/phanan/koel/wiki#streaming-music
     *
     * @param Song $song
     */
    public function play(Song $song)
    {
        if (ends_with(mime_content_type($song->path), 'flac')) {
            return (new TranscodingStreamer($song))->stream();
        }

        switch (env('STREAMING_METHOD')) {
            case 'x-sendfile':
                return (new XSendFileStreamer($song))->stream();
            case 'x-accel-redirect':
                return (new XAccelRedirectStreamer($song))->stream();
            default:
                return (new PHPStreamer($song))->stream();
        }
    }

    /**
     * Get extra information about a song via Last.fm.
     *
     * @param Song $song
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Song $song)
    {
        return response()->json([
            'lyrics' => $song->lyrics,
            'album_info' => $song->album->getInfo(),
            'artist_info' => $song->artist->getInfo(),
        ]);
    }

    /**
     * Update songs info.
     *
     * @param SongUpdateRequest $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(SongUpdateRequest $request)
    {
        return response()->json(Song::updateInfo($request->songs, $request->data));
    }
}
