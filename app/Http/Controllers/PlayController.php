<?php

namespace App\Http\Controllers;

use App\Factories\StreamerFactory;
use App\Http\Requests\SongPlayRequest;
use App\Models\Song;

class PlayController extends Controller
{
    public function __construct(private StreamerFactory $streamerFactory)
    {
    }

    public function show(SongPlayRequest $request, Song $song, ?bool $transcode = null, ?int $bitRate = null)
    {
        return $this->streamerFactory
            ->createStreamer($song, $transcode, $bitRate, (float) $request->time)
            ->stream();
    }
}
