<?php

namespace App\Http\Streamers;

use App\Models\Song;

class XSendFileStreamer extends Streamer implements StreamerInterface
{
    public function __construct(Song $song)
    {
        parent::__construct($song);
    }

    /**
     * Stream the current song using Apache's x_sendfile module.
     */
    public function stream()
    {
        header("X-Sendfile: {$this->song->path}");
        header("Content-Type: {$this->contentType}");
        header('Content-Disposition: inline; filename="'.basename($this->song->path).'"');

        exit;
    }
}
