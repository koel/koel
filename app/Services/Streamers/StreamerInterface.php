<?php

namespace App\Services\Streamers;

use App\Models\Song;

interface StreamerInterface
{
    public function setSong(Song $song);
    public function stream();
}
