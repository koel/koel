<?php

namespace App\Services\Transcoding;

use App\Enums\TranscodeCodec;
use App\Exceptions\TranscodingFailedException;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

class Transcoder
{
    public function __construct(
        #[Config('koel.streaming.transcode_timeout')]
        private readonly int $transcodeTimeout = 0,
        #[Config('koel.streaming.ffmpeg_path')]
        private readonly ?string $ffmpegPath = null,
        #[Config('koel.streaming.aac_fast')]
        private readonly bool $aacFast = true,
        #[Config('koel.streaming.transcode_codec')]
        private readonly TranscodeCodec $configuredCodec = TranscodeCodec::AAC,
    ) {}

    /**
     * The codec to use for new transcodes: the configured one, or the default codec
     * if FFmpeg lacks support for the configured one.
     */
    public function preferredCodec(): TranscodeCodec
    {
        return $this->supports($this->configuredCodec) ? $this->configuredCodec : TranscodeCodec::default();
    }

    public function transcode(string $source, string $destination, int $bitRate, TranscodeCodec $codec): void
    {
        setlocale(LC_CTYPE, 'en_US.UTF-8'); // #1481 special chars might be stripped otherwise

        File::ensureDirectoryExists(dirname($destination));

        $process = $this->transcodeTimeout ? Process::timeout($this->transcodeTimeout) : Process::forever();

        $command = [
            $this->ffmpegPath,
            '-nostdin',
            '-i',
            $source,
            '-vn', // Strip video
            ...match ($codec) {
                TranscodeCodec::AAC => [
                    '-c:a',
                    'aac',
                    '-b:a',
                    "{$bitRate}k",
                    ...($this->aacFast ? ['-aac_coder', 'fast'] : []),
                    '-threads',
                    '0',
                    '-movflags',
                    '+faststart',
                ],
                TranscodeCodec::OPUS => [
                    '-c:a',
                    'libopus',
                    '-b:a',
                    "{$bitRate}k",
                    '-f',
                    'webm',
                ],
            },
            '-y',
            $destination,
        ];

        $result = $process->run($command);

        throw_if($result->failed(), new TranscodingFailedException($result->errorOutput()));
    }

    public function supports(TranscodeCodec $codec): bool
    {
        if ($codec === TranscodeCodec::AAC) {
            return true;
        }

        if (!$this->ffmpegPath || !is_executable($this->ffmpegPath)) {
            return false;
        }

        try {
            $cacheKey = sprintf('ffmpeg-supports-libopus:%s', hash('sha256', sprintf(
                '%s:%d',
                $this->ffmpegPath,
                File::lastModified($this->ffmpegPath),
            )));

            return Cache::remember($cacheKey, now()->addDay(), $this->hasLibopusEncoder(...));
        } catch (Throwable $e) {
            Log::warning('Could not determine FFmpeg libopus support. Falling back to AAC.', ['exception' => $e]);

            return false;
        }
    }

    private function hasLibopusEncoder(): bool
    {
        $result = Process::timeout(10)->run([
            $this->ffmpegPath,
            '-hide_banner',
            '-loglevel',
            'error',
            '-h',
            'encoder=libopus',
        ]);

        $supported =
            $result->successful() && str_contains($result->output() . $result->errorOutput(), 'Encoder libopus ');

        if (!$supported) {
            Log::warning('FFmpeg lacks the libopus encoder. Opus transcoding will fall back to AAC.');
        }

        return $supported;
    }

    /** @param callable(string): void $onAudioChunk */
    public function streamProgressively(string $source, int $bitRate, float $startTime, callable $onAudioChunk): void
    {
        setlocale(LC_CTYPE, 'en_US.UTF-8');

        $process = $this->transcodeTimeout ? Process::timeout($this->transcodeTimeout) : Process::forever();
        $startTimeArguments = $startTime > 0 ? ['-ss', (string) $startTime] : [];
        $timestampArguments = $startTime > 0 ? ['-output_ts_offset', (string) $startTime] : [];
        $outputHandler = new ProgressiveTranscodeOutputHandler($onAudioChunk);

        $invokedProcess = $process->quietly()->start([
            $this->ffmpegPath,
            '-hide_banner',
            '-loglevel',
            'error',
            '-nostdin',
            ...$startTimeArguments,
            '-i',
            $source,
            '-map',
            '0:a:0',
            '-vn',
            '-c:a',
            'libopus',
            '-application',
            'audio',
            '-b:a',
            "{$bitRate}k",
            ...$timestampArguments,
            '-f',
            'webm',
            '-live',
            '1',
            '-cluster_time_limit',
            '1000',
            '-flush_packets',
            '1',
            'pipe:1',
        ], $outputHandler);

        try {
            $outputHandler->throwIfFailed();
            $result = $invokedProcess->waitUntil($outputHandler);
            $outputHandler->throwIfFailed();
        } catch (Throwable $e) {
            ProgressiveProcessTerminator::stop($invokedProcess, $outputHandler);

            throw $e;
        }

        throw_if($result->failed(), new TranscodingFailedException($outputHandler->errorOutput()));
    }

    public function finalizeProgressiveTranscode(string $source, string $destination): void
    {
        File::ensureDirectoryExists(dirname($destination));

        $process = $this->transcodeTimeout ? Process::timeout($this->transcodeTimeout) : Process::forever();
        $result = $process->run([
            $this->ffmpegPath,
            '-hide_banner',
            '-loglevel',
            'error',
            '-nostdin',
            '-i',
            $source,
            '-map',
            '0:a:0',
            '-c:a',
            'copy',
            '-f',
            'webm',
            '-y',
            $destination,
        ]);

        throw_if($result->failed(), new TranscodingFailedException($result->errorOutput()));
    }
}
