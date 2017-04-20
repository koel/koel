<?php

namespace App\Services\Streamers;

interface StreamerInterface
{
    /**
     * Stream the current song.
     */
    public function stream();
}
