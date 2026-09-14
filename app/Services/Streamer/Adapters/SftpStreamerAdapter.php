<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Song;
use App\Services\SongStorages\SftpStorage;
use App\Services\Streamer\Adapters\Concerns\StreamsLocalPath;
use App\Values\RequestedStreamingConfig;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SftpStreamerAdapter implements StreamerAdapter
{
    use StreamsLocalPath;

    public function __construct(
        private readonly SftpStorage $storage,
    ) {}

    public function stream(Song $song, ?RequestedStreamingConfig $config = null): BinaryFileResponse
    {
        return self::streamLocalPath($this->storage->copyToLocal($song->storage_metadata->getPath()));
    }
}
