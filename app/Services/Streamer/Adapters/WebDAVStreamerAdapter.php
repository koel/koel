<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Song;
use App\Services\SongStorages\WebDAVStorage;
use App\Services\Streamer\Adapters\Concerns\StreamsLocalPath;
use App\Values\RequestedStreamingConfig;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WebDAVStreamerAdapter implements StreamerAdapter
{
    use StreamsLocalPath;

    public function __construct(
        private readonly WebDAVStorage $storage,
    ) {}

    public function stream(Song $song, ?RequestedStreamingConfig $config = null): BinaryFileResponse
    {
        $this->storage->assertSupported();
        return self::streamLocalPath($this->storage->copyToLocal($song->storage_metadata->getPath()));
    }
}
