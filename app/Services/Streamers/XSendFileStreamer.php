<?php

namespace App\Services\Streamers;

class XSendFileStreamer extends Streamer implements DirectStreamerInterface
{
    /**
     * Stream the current song using Apache's x_sendfile module.
     */
    public function stream(): void
    {
        header("X-Sendfile: {$this->song->path}");
        header("Content-Type: $this->contentType");
        header('Content-Disposition: inline; filename="' . basename($this->song->path) . '"');

        exit;
    }
}
