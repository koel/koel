<?php

namespace App\Http\Controllers;

use App\Http\Requests\SongPlayRequest;
use App\Models\Song;
use App\Services\Streamers\StreamerFactory;

class PlayController extends Controller
{
    public function __invoke(
        StreamerFactory $streamerFactory,
        SongPlayRequest $request,
        Song $song,
        ?bool $transcode = null,
        ?int $bitRate = null
    ) {
        $this->authorize('access', $song);

        return $streamerFactory
            ->createStreamer($song, $transcode, $bitRate, (float) $request->time)
            ->stream();
    }
}
