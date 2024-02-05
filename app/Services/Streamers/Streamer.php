<?php

namespace App\Services\Streamers;

use App\Models\Song;

abstract class Streamer
{
    protected ?Song $song = null;
    protected ?string $contentType = null;

    public function __construct()
    {
        // Turn off error reporting to make sure our stream isn't interfered.
        @error_reporting(0);
    }

    public function setSong(Song $song): void
    {
        $this->song = $song;
    }

    abstract public function stream(): mixed;
}
