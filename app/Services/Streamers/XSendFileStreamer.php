<?php

namespace App\Services\Streamers;

class XSendFileStreamer extends Streamer implements StreamerInterface
{
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
