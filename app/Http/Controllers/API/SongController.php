<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\SongUpdateRequest;
use App\Http\Streamers\PHPStreamer;
use App\Http\Streamers\S3Streamer;
use App\Http\Streamers\TranscodingStreamer;
use App\Http\Streamers\XAccelRedirectStreamer;
use App\Http\Streamers\XSendFileStreamer;
use App\Models\Song;
use YouTube;

class SongController extends Controller
{
    /**
     * Play a song.
     *
     * @link https://github.com/phanan/koel/wiki#streaming-music
     *
     * @param Song $song
     */
    public function play(Song $song, $transcode = null, $bitrate = null)
    {
        if (is_null($bitrate)) {
            $bitrate = env('OUTPUT_BIT_RATE', 128);
        }

        if ($song->s3_params) {
            return (new S3Streamer($song))->stream();
        }

        // If transcode parameter isn't passed, the default is to only transcode flac
        if (is_null($transcode) && ends_with(mime_content_type($song->path), 'flac')) {
            $transcode = true;
        } else {
            $transcode = (bool) $transcode;
        }

        if ($transcode) {
            return (new TranscodingStreamer($song, $bitrate, request()->input('time', 0)))->stream();
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
            'youtube' => YouTube::searchVideosRelatedToSong($song),
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
