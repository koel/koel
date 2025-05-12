<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Song;
use App\Services\Streamer\Adapters\Concerns\StreamsLocalPath;
use App\Values\RequestedStreamingConfig;
use App\Values\TranscodeResult;

class TranscodingStreamerAdapter implements StreamerAdapter
{
    use StreamsLocalPath;

    public function stream(Song $song, ?RequestedStreamingConfig $config = null): void
    {
        abort_unless(is_executable(config('koel.streaming.ffmpeg_path')), 500, 'ffmpeg not found or not executable.');

        $bitRate = $config?->bitRate ?: config('koel.streaming.bitrate');

        $this->streamLocalPath(TranscodeResult::getForSong($song, $bitRate)->path);
    }
}
