<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Song;
use App\Services\SongStorages\SftpStorage;
use App\Services\Streamer\Adapters\Concerns\StreamsLocalPath;

class SftpStreamerAdapter implements StreamerAdapter
{
    use StreamsLocalPath;

    public function __construct(private readonly SftpStorage $storage)
    {
    }

    public function stream(Song $song, array $config = []): void
    {
        $this->streamLocalPath($this->storage->copyToLocal($song));
    }
}
