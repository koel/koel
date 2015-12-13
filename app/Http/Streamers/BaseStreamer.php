<?php

namespace App\Http\Streamers;

use App\Models\Song;

class BaseStreamer
{
    /**
     * @var Song|string
     */
    protected $song;

    /**
     * @var string
     */
    protected $contentType;

    /**
     * BaseStreamer constructor.
     *
     * @param $song Song|string A Song object, or its ID.
     */
    public function __construct($song)
    {
        $this->song = $song instanceof Song ? $song : Song::findOrFail($song);

        if (!file_exists($this->song->path)) {
            abort(404);
        }

        // Hard code the content type instead of relying on PHP's fileinfo()
        // or even Symfony's MIMETypeGuesser, since they appear to be wrong sometimes.
        $this->contentType = 'audio/'.pathinfo($this->song->path, PATHINFO_EXTENSION);

        // Turn off error reporting to make sure our stream isn't interfered.
        @error_reporting(0);
    }
}
