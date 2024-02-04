<?php

namespace App\Services\Streamers;

use App\Models\Song;
use App\Values\SongStorageMetadata\LocalMetadata;

class Streamer implements StreamerInterface
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

        // Hard code the content type instead of relying on PHP's fileinfo()
        // or even Symfony's MIMETypeGuesser, since they appear to be wrong sometimes.
        if ($this->song->storage_metadata instanceof LocalMetadata) {
            $this->contentType = 'audio/' . pathinfo($this->song->storage_metadata->getPath(), PATHINFO_EXTENSION);
        }
    }
}
