<?php

namespace App\Services\Streamers;

use App\Models\Song;

class Streamer
{
    /**
     * @var Song|string
     */
    protected $song;

    /**
     * @var string
     */
    protected $contentType;

    public function __construct()
    {
        // Turn off error reporting to make sure our stream isn't interfered.
        @error_reporting(0);
    }

    public function setSong(Song $song)
    {
        $this->song = $song;

        abort_unless($this->song->s3_params || file_exists($this->song->path), 404);

        // Hard code the content type instead of relying on PHP's fileinfo()
        // or even Symfony's MIMETypeGuesser, since they appear to be wrong sometimes.
        if (!$this->song->s3_params) {
            $this->contentType = 'audio/'.pathinfo($this->song->path, PATHINFO_EXTENSION);
        }
    }
}
