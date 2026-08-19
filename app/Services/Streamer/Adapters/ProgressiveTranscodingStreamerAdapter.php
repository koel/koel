<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Song;
use App\Services\Transcoding\ProgressiveTranscodeSession;
use App\Values\RequestedStreamingConfig;
use Illuminate\Container\Attributes\Config;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProgressiveTranscodingStreamerAdapter implements StreamerAdapter
{
    public function __construct(
        private readonly ProgressiveTranscodeSession $session,
        private readonly TranscodingStreamerAdapter $completedTranscodeAdapter,
        #[Config('koel.streaming.bitrate')]
        private readonly int $defaultBitRate,
        #[Config('koel.streaming.ffmpeg_path')]
        private readonly ?string $ffmpegPath,
    ) {}

    public function stream(Song $song, ?RequestedStreamingConfig $config = null)
    {
        abort_unless(
            $this->ffmpegPath !== null && is_executable($this->ffmpegPath),
            Response::HTTP_INTERNAL_SERVER_ERROR,
            'ffmpeg not found or not executable.',
        );

        $bitRate = $config?->bitRate ?: $this->defaultBitRate;
        $startTime = $config->startTime ?? 0.0;
        $preparedStream = $this->session->prepare($song, $bitRate, $startTime);

        if (is_string($preparedStream)) {
            return $this->completedTranscodeAdapter->streamTranscodeLocation($preparedStream);
        }

        return new StreamedResponse($preparedStream, headers: [
            'Content-Type' => 'audio/webm; codecs=opus',
            'Cache-Control' => 'no-store',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
