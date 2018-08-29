<?php

namespace App\Services;

use App\Models\Song;

interface ObjectStorageInterface
{
    /**
     * Get a song's Object Storage url for streaming or downloading.
     */
    public function getSongPublicUrl(Song $song): string;
}
