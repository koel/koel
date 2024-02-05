<?php

namespace App\Services\Streamers;

use App\Models\Song;

abstract class LocalStreamer extends Streamer
{
    protected function supported(): bool
    {
        return true;
    }

    public function setSong(Song $song): void
    {
        $this->song = $song;

        // Hard code the content type instead of relying on PHP's fileinfo()
        // or even Symfony's MIMETypeGuesser, since they appear to be wrong sometimes.
        $this->contentType = 'audio/' . pathinfo($this->song->storage_metadata->getPath(), PATHINFO_EXTENSION);
    }
}
