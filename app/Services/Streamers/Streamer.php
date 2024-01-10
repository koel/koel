<?php

namespace App\Services\Streamers;

use App\Models\Song;
use Illuminate\Support\Facades\File;

class Streamer
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

        abort_unless($this->song->s3_params || File::exists($this->song->path), 404);

        // Hard code the content type instead of relying on PHP's fileinfo()
        // or even Symfony's MIMETypeGuesser, since they appear to be wrong sometimes.
        if (!$this->song->s3_params) {
            $this->contentType = 'audio/' . pathinfo($this->song->path, PATHINFO_EXTENSION);
        }
    }
}
