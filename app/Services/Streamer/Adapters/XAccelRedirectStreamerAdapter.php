<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Setting;
use App\Models\Song;

class XAccelRedirectStreamerAdapter extends LocalStreamerAdapter
{
    /**
     * Stream the current song using nginx's X-Accel-Redirect.
     * @link https://www.nginx.com/resources/wiki/start/topics/examples/xsendfile/
     */
    public function stream(Song $song, array $config = []): void
    {
        $path = $song->storage_metadata->getPath();
        $contentType = 'audio/' . pathinfo($path, PATHINFO_EXTENSION);
        $relativePath = str_replace(Setting::get('media_path'), '', $path);

        // We send our media_path value as a 'X-Media-Root' header to downstream (nginx)
        // It will then be use as `alias` in X-Accel config location block.
        // See nginx.conf.example.
        header('X-Media-Root: ' . Setting::get('media_path'));
        header("X-Accel-Redirect: /media/$relativePath");
        header("Content-Type: $contentType");
        header('Content-Disposition: inline; filename="' . basename($path) . '"');

        exit;
    }
}
