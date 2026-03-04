<?php

namespace App\Http\Controllers;

use App\Models\Embed;
use App\Models\Song;
use App\Services\Streamer\Streamer;

class StreamEmbedController extends Controller
{
    public function __invoke(Embed $embed, Song $song)
    {
        return (new Streamer(song: $song))->stream();
    }
}
