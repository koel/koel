<?php

namespace App\Http\Controllers;

use App\Factories\StreamerFactory;
use App\Http\Requests\SongPlayRequest;
use App\Models\Song;
use App\Services\TokenManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

class PlayController extends Controller
{
    private $tokenManager;
    private $streamerFactory;

    public function __construct(TokenManager $tokenManager, StreamerFactory $streamerFactory)
    {
        $this->tokenManager = $tokenManager;
        $this->streamerFactory = $streamerFactory;
    }

    /**
     * Play a song
     *
     * The GET request to play/stream a song. By default Koel will serve the file as-is, unless it's a FLAC.
     * If the value of `transcode` is truthy, Koel will attempt to transcode the file into `bitRate`kbps using ffmpeg.
     *
     * @response {}
     *
     * @queryParam api_token required The API token.
     *
     * @see https://github.com/phanan/koel/wiki#streaming-music
     *
     * @param bool|null $transcode Whether to force transcoding the song.
     *                             If this is omitted, by default Koel will transcode FLAC.
     * @param int|null  $bitRate   The target bit rate to transcode, defaults to OUTPUT_BIT_RATE.
     *                             Only taken into account if $transcode is truthy.
     *
     * @return RedirectResponse|Redirector
     */
    public function show(SongPlayRequest $request, Song $song, ?bool $transcode = null, ?int $bitRate = null)
    {
        return $this->streamerFactory
            ->createStreamer($song, $transcode, $bitRate, floatval($request->time))
            ->stream();
    }
}
