<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Song;
use App\Services\Streamer\Adapters\Concerns\StreamsLocalPath;

class PhpStreamerAdapter extends LocalStreamerAdapter
{
    use StreamsLocalPath;

    public function stream(Song $song, array $config = []): void
    {
        $this->streamLocalPath($song->storage_metadata->getPath());
    }
}
