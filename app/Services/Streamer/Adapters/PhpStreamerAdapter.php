<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Song;
use App\Services\Streamer\Adapters\Concerns\StreamsLocalPath;
use App\Values\RequestedStreamingConfig;

class PhpStreamerAdapter extends LocalStreamerAdapter
{
    use StreamsLocalPath;

    public function stream(Song $song, ?RequestedStreamingConfig $config = null): void
    {
        $this->streamLocalPath($song->storage_metadata->getPath());

        // For PHP streamer, we explicitly exit here to prevent the framework from sending additional headers
        // and causing "headers already sent" errors (#2054).
        exit;
    }
}
