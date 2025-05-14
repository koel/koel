<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Song;
use App\Values\RequestedStreamingConfig;

class XSendFileStreamerAdapter extends LocalStreamerAdapter
{
    /**
     * Stream the current song using Apache's x_sendfile module.
     */
    public function stream(Song $song, ?RequestedStreamingConfig $config = null): never
    {
        $path = $song->storage_metadata->getPath();
        $contentType = 'audio/' . pathinfo($path, PATHINFO_EXTENSION);

        header("X-Sendfile: $path");
        header("Content-Type: $contentType");
        header('Content-Disposition: inline; filename="' . basename($path) . '"');

        // prevent PHP from sending stray headers
        exit;
    }
}
