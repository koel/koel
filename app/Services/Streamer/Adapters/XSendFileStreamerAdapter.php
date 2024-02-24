<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Song;

class XSendFileStreamerAdapter extends LocalStreamerAdapter
{
    /**
     * Stream the current song using Apache's x_sendfile module.
     */
    public function stream(Song $song, array $config = []): void
    {
        $path = $song->storage_metadata->getPath();
        $contentType = 'audio/' . pathinfo($path, PATHINFO_EXTENSION);

        header("X-Sendfile: $path");
        header("Content-Type: $contentType");
        header('Content-Disposition: inline; filename="' . basename($path) . '"');

        exit;
    }
}
