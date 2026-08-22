<?php

namespace App\Services\Streamer\Adapters;

use App\Enums\TranscodeCodec;
use App\Models\Song;
use App\Services\Streamer\Adapters\Concerns\StreamsLocalPath;
use App\Services\Transcoding\TranscodeStrategyFactory;
use App\Values\RequestedStreamingConfig;
use Illuminate\Container\Attributes\Config;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TranscodingStreamerAdapter implements StreamerAdapter
{
    use StreamsLocalPath;

    public function __construct(
        #[Config('koel.streaming.bitrate')]
        private readonly int $defaultBitRate,
    ) {}

    public function stream(Song $song, ?RequestedStreamingConfig $config = null)
    {
        abort_unless(
            is_executable(config('koel.streaming.ffmpeg_path')),
            Response::HTTP_INTERNAL_SERVER_ERROR,
            'ffmpeg not found or not executable.',
        );

        $bitRate = $config?->bitRate ?: $this->defaultBitRate;

        $transcodePath = TranscodeStrategyFactory::make($song->storage)->getTranscodeLocation($song, $bitRate);

        if (Str::startsWith($transcodePath, ['http://', 'https://'])) {
            return response()->redirectTo($transcodePath);
        }

        $mimeType = TranscodeCodec::fromExtension(File::extension($transcodePath))->mimeType();
        $this->streamLocalPath($transcodePath, $mimeType);
    }
}
