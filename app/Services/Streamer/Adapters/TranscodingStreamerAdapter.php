<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Song;
use App\Services\Streamer\Adapters\Concerns\StreamsLocalPath;
use App\Services\Transcoding\TranscodeStrategyFactory;
use App\Values\RequestedStreamingConfig;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class TranscodingStreamerAdapter implements StreamerAdapter
{
    use StreamsLocalPath;

    public function stream(Song $song, ?RequestedStreamingConfig $config = null) // @phpcs:ignore
    {
        abort_unless(
            is_executable(config('koel.streaming.ffmpeg_path')),
            Response::HTTP_INTERNAL_SERVER_ERROR,
            'ffmpeg not found or not executable.'
        );

        $bitRate = $config?->bitRate ?: config('koel.streaming.bitrate');

        $transcodePath = TranscodeStrategyFactory::make($song->storage)->getTranscodeLocation($song, $bitRate);

        if (Str::startsWith($transcodePath, ['http://', 'https://'])) {
            return response()->redirectTo($transcodePath);
        }

        $this->streamLocalPath($transcodePath);
    }
}
