<?php

namespace App\Http\Streamers;

class XSendFileStreamer extends BaseStreamer implements StreamerInterface
{
    public function __construct($id)
    {
        parent::__construct($id);
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
