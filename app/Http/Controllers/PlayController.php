<?php

namespace App\Http\Controllers;

use App\Factories\StreamerFactory;
use App\Http\Requests\SongPlayRequest;
use App\Models\Song;
use App\Services\TokenManager;

class PlayController extends Controller
{
    private $tokenManager;
    private $streamerFactory;

    public function __construct(TokenManager $tokenManager, StreamerFactory $streamerFactory)
    {
        $this->tokenManager = $tokenManager;
        $this->streamerFactory = $streamerFactory;
    }

    public function show(SongPlayRequest $request, Song $song, ?bool $transcode = null, ?int $bitRate = null)
    {
        return $this->streamerFactory
            ->createStreamer($song, $transcode, $bitRate, floatval($request->time))
            ->stream();
    }
}
