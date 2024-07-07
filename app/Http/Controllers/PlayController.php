<?php

namespace App\Http\Controllers;

use App\Http\Requests\SongPlayRequest;
use App\Models\Song;
use App\Services\Streamer\Streamer;

class PlayController extends Controller
{
    public function __invoke(SongPlayRequest $request, Song $song, ?bool $transcode = null, ?int $bitRate = null)
    {
        $this->authorize('access', $song);

        return (new Streamer(song: $song, config: [
            'transcode' => (bool) $transcode,
            'bit_rate' => $bitRate,
            'start_time' => (float) $request->time,
        ]))->stream();
    }
}
