<?php

namespace App\Http\Streamers;

interface StreamerInterface
{
    /**
     * Stream the current song.
     */
    public function stream();
}
