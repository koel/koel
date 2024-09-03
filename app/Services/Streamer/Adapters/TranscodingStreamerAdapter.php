<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Song;
use App\Services\Streamer\Adapters\Concerns\StreamsLocalPath;
use App\Values\TranscodeResult;
use Illuminate\Support\Arr;

class TranscodingStreamerAdapter implements StreamerAdapter
{
    use StreamsLocalPath;

    public function stream(Song $song, array $config = []): void
    {
        abort_unless(is_executable(config('koel.streaming.ffmpeg_path')), 500, 'ffmpeg not found or not executable.');

        $bitRate = filter_var(Arr::get($config, 'bit_rate'), FILTER_SANITIZE_NUMBER_INT)
            ?: config('koel.streaming.bitrate');

        $this->streamLocalPath(TranscodeResult::getForSong($song, $bitRate)->path);
    }
}
