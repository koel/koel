<?php

namespace App\Services\Transcoding;

use App\Exceptions\TranscodingFailedException;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class Transcoder
{
    /**
     * AAC sample rates up to 48 kHz. A source above that (hi-res FLAC at 88.2, 96 or 192 kHz) would otherwise keep its
     * rate, spending the bit rate on frequencies nobody hears; FFmpeg resamples it to the nearest of these instead.
     */
    private const string SAMPLE_RATES_UP_TO_48_KHZ = '7350|8000|11025|12000|16000|22050|24000|32000|44100|48000';

    public function __construct(
        #[Config('koel.streaming.transcode_timeout')]
        private readonly int $transcodeTimeout = 0,
        #[Config('koel.streaming.ffmpeg_path')]
        private readonly string $ffmpegPath = '',
        #[Config('koel.streaming.aac_fast')]
        private readonly bool $aacFast = true,
    ) {}

    public function transcode(string $source, string $destination, int $bitRate): void
    {
        setlocale(LC_CTYPE, 'en_US.UTF-8'); // #1481 special chars might be stripped otherwise

        File::ensureDirectoryExists(dirname($destination));

        $process = $this->transcodeTimeout ? Process::timeout($this->transcodeTimeout) : Process::forever();

        $result = $process->run([
            $this->ffmpegPath,
            '-nostdin',
            '-i',
            $source,
            '-vn', // Strip video
            '-af',
            'aformat=sample_rates=' . self::SAMPLE_RATES_UP_TO_48_KHZ,
            ...self::resolveAacEncoderArguments($this->ffmpegPath, $bitRate, $this->aacFast),
            '-threads',
            '0',
            '-movflags',
            '+faststart', // Place moov atom at the start for faster streaming
            '-y', // Overwrite if exists
            $destination,
        ]);

        throw_if($result->failed(), new TranscodingFailedException($result->errorOutput()));
    }

    /**
     * Fraunhofer's libfdk_aac encodes better AAC than FFmpeg's own encoder at the same bit rate, but FFmpeg
     * only ships with it when built with --enable-nonfree, so it is used whenever the installed FFmpeg has it.
     *
     * @return array<string>
     */
    private static function resolveAacEncoderArguments(string $ffmpegPath, int $bitRate, bool $aacFast): array
    {
        if (self::ffmpegHasFdkAacEncoder($ffmpegPath)) {
            return ['-c:a', 'libfdk_aac', '-b:a', "{$bitRate}k"];
        }

        return ['-c:a', 'aac', '-b:a', "{$bitRate}k", ...($aacFast ? ['-aac_coder', 'fast'] : [])];
    }

    private static function ffmpegHasFdkAacEncoder(string $ffmpegPath): bool
    {
        $lastModified = is_file($ffmpegPath) ? filemtime($ffmpegPath) : 0;

        return Cache::rememberForever(
            cache_key('ffmpeg has libfdk_aac', $ffmpegPath, $lastModified),
            static fn (): bool => str_contains(
                Process::run([$ffmpegPath, '-hide_banner', '-encoders'])->output(),
                ' libfdk_aac ',
            ),
        );
    }
}
