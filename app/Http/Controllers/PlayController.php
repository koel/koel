<?php

namespace App\Http\Controllers;

use App\Factories\StreamerFactory;
use App\Http\Requests\SongPlayRequest;
use App\Models\Song;

class PlayController extends Controller
{
    public function __invoke(
        StreamerFactory $streamerFactory,
        SongPlayRequest $request,
        Song $song,
        ?bool $transcode = null,
        ?int $bitRate = null
    ) {
        $this->authorize('play', $song);

        return $streamerFactory
            ->createStreamer($song, $transcode, $bitRate, (float) $request->time)
            ->stream();
    }
}
