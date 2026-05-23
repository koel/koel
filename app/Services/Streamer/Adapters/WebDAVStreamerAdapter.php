<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Song;
use App\Services\SongStorages\WebDAVStorage;
use App\Services\Streamer\Adapters\Concerns\StreamsLocalPath;
use App\Values\RequestedStreamingConfig;

class WebDAVStreamerAdapter implements StreamerAdapter
{
    use StreamsLocalPath;

    public function __construct(
        private readonly WebDAVStorage $storage,
    ) {}

    public function stream(Song $song, ?RequestedStreamingConfig $config = null): void
    {
        $this->storage->assertSupported();
        $this->streamLocalPath($this->storage->copyToLocal($song->storage_metadata->getPath()));
    }
}
