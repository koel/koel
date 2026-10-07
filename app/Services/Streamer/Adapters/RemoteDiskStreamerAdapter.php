<?php

namespace App\Services\Streamer\Adapters;

use App\Http\Responses\StreamedFileResponse;
use App\Models\Song;
use App\Services\SongStorages\RemoteDiskStorage;
use App\Services\Streamer\Adapters\Concerns\StreamsLocalPath;
use App\Values\RequestedStreamingConfig;

class RemoteDiskStreamerAdapter implements StreamerAdapter
{
    use StreamsLocalPath;

    public function __construct(
        private readonly RemoteDiskStorage $storage,
    ) {}

    public function stream(Song $song, ?RequestedStreamingConfig $config = null): StreamedFileResponse
    {
        $this->storage->assertSupported();

        return self::streamLocalPath($this->storage->copyToLocal($song->storage_metadata->getPath()));
    }
}
