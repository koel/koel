<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Setting;
use App\Models\Song;
use App\Values\RequestedStreamingConfig;

class XAccelRedirectStreamerAdapter extends LocalStreamerAdapter
{
    /**
     * Stream the current song using nginx's X-Accel-Redirect.
     * @link https://www.nginx.com/resources/wiki/start/topics/examples/xsendfile/
     */
    public function stream(Song $song, ?RequestedStreamingConfig $config = null): never
    {
        $path = $song->storage_metadata->getPath();
        $contentType = 'audio/' . pathinfo($path, PATHINFO_EXTENSION);
        $relativePath = str_replace(Setting::get('media_path'), '', $path);

        // We send our media_path value as an 'X-Media-Root' header to downstream (nginx)
        // It will then be used as `alias` in X-Accel config location block.
        // See nginx.conf.example.
        header('X-Media-Root: ' . Setting::get('media_path'));
        header("X-Accel-Redirect: /media/$relativePath");
        header("Content-Type: $contentType");
        header('Content-Disposition: inline; filename="' . basename($path) . '"');

        // prevent PHP from sending stray headers
        exit;
    }
}
