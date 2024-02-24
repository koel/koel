<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Song;

interface StreamerAdapter
{
    public function stream(Song $song, array $config = []); // @phpcs:ignore
}
