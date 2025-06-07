<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Song;
use App\Services\Streamer\Adapters\Concerns\StreamsLocalPath;
use App\Services\Transcoder;
use App\Values\RequestedStreamingConfig;
use Illuminate\Http\Response;

class TranscodingStreamerAdapter implements StreamerAdapter
{
    use StreamsLocalPath;

    public function __construct(private readonly Transcoder $transcoder)
    {
    }

    public function stream(Song $song, ?RequestedStreamingConfig $config = null): void
    {
        abort_unless(
            is_executable(config('koel.streaming.ffmpeg_path')),
            Response::HTTP_INTERNAL_SERVER_ERROR,
            'ffmpeg not found or not executable.'
        );

        $bitRate = $config?->bitRate ?: config('koel.streaming.bitrate');

        $this->streamLocalPath($this->transcoder->getTranscodedPath($song, $bitRate));
    }
}
