<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\API\SongPlayRequest;
use App\Http\Requests\API\SongUpdateRequest;
use App\Models\Song;
use App\Services\Streamers\PHPStreamer;
use App\Services\Streamers\S3Streamer;
use App\Services\Streamers\TranscodingStreamer;
use App\Services\Streamers\XAccelRedirectStreamer;
use App\Services\Streamers\XSendFileStreamer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

class SongController extends Controller
{
    /**
     * Play/stream a song.
     *
     * @link https://github.com/phanan/koel/wiki#streaming-music
     *
     * @param SongPlayRequest $request
     * @param Song            $song      The song to stream.
     * @param null|bool       $transcode Whether to force transcoding the song.
     *                                   If this is omitted, by default Koel will transcode FLAC.
     * @param null|int        $bitRate   The target bit rate to transcode, defaults to OUTPUT_BIT_RATE.
     *                                   Only taken into account if $transcode is truthy.
     *
     * @return RedirectResponse|Redirector
     */
    public function play(SongPlayRequest $request, Song $song, $transcode = null, $bitRate = null)
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
                floatval($request->time)
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
     * @return JsonResponse
     */
    public function show(Song $song)
    {
        return response()->json([
            'lyrics' => $song->lyrics,
            'album_info' => $song->album->getInfo(),
            'artist_info' => $song->artist->getInfo(),
            'youtube' => $song->getRelatedYouTubeVideos(),
        ]);
    }

    /**
     * Update songs info.
     *
     * @param SongUpdateRequest $request
     *
     * @return JsonResponse
     */
    public function update(SongUpdateRequest $request)
    {
        return response()->json(Song::updateInfo($request->songs, $request->data));
    }
}
