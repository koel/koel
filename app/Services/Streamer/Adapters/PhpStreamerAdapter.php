<?php

namespace App\Services\Streamer\Adapters;

use App\Http\Responses\StreamedFileResponse;
use App\Models\Song;
use App\Services\Streamer\Adapters\Concerns\StreamsLocalPath;
use App\Values\RequestedStreamingConfig;

class PhpStreamerAdapter extends LocalStreamerAdapter
{
    use StreamsLocalPath;

    public function stream(Song $song, ?RequestedStreamingConfig $config = null): StreamedFileResponse
    {
        return self::streamLocalPath($song->storage_metadata->getPath());
    }
}
