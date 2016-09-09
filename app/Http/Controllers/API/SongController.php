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
     * Play/stream a song.
     *
     * @link https://github.com/phanan/koel/wiki#streaming-music
     *
     * @param Song      $song      The song to stream.
     * @param null|bool $transcode Whether to force transcoding the song.
     *                             If this is omitted, by default Koel will transcode FLAC.
     * @param null|int  $bitRate   The target bit rate to transcode, defaults to OUTPUT_BIT_RATE.
     *                             Only taken into account if $transcode is truthy.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function play(Song $song, $transcode = null, $bitRate = null)
    {
        if ($song->s3_params) {
            return (new S3Streamer($song))->stream();
        }

        // If `transcode` parameter isn't passed, the default is to only transcode FLAC.
        if ($transcode === null && ends_with(mime_content_type($song->path), 'flac')) {
            $transcode = true;
        }

        $streamer = null;

        if ($transcode) {
            $streamer = new TranscodingStreamer(
                $song,
                $bitRate ?: config('koel.streaming.bitrate'),
                request()->input('time', 0)
            );
        } else {
            switch (config('koel.streaming.method')) {
                case 'x-sendfile':
                    $streamer = new XSendFileStreamer($song);
                    break;
                case 'x-accel-redirect':
                    $streamer = new XAccelRedirectStreamer($song);
                    break;
                default:
                    $streamer = new PHPStreamer($song);
                    break;
            }
        }

        $streamer->stream();
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
