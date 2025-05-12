<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Song;
use App\Values\RequestedStreamingConfig;

interface StreamerAdapter
{
    public function stream(Song $song, ?RequestedStreamingConfig $config = null); // @phpcs:ignore
}
