<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Song;
use App\Services\SongStorages\SftpStorage;
use App\Services\Streamer\Adapters\Concerns\StreamsLocalPath;
use App\Values\RequestedStreamingConfig;

class SftpStreamerAdapter implements StreamerAdapter
{
    use StreamsLocalPath;

    public function __construct(private readonly SftpStorage $storage)
    {
    }

    public function stream(Song $song, ?RequestedStreamingConfig $config = null): void
    {
        $this->streamLocalPath($this->storage->copyToLocal($song->storage_metadata->getPath()));
    }
}
