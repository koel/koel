<?php

namespace Tests\Unit\Services\Transcoding;

use App\Enums\TranscodeCodec;
use App\Exceptions\TranscodingFailedException;
use App\Services\Transcoding\Transcoder;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class TranscoderTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config(['koel.streaming.ffmpeg_path' => '/usr/bin/ffmpeg']);
    }

    #[Test]
    public function transcode(): void
    {
        Process::fake();
        File::expects('ensureDirectoryExists')->with('/path/to');

        $transcoder = new Transcoder(transcodeTimeout: 300, ffmpegPath: '/usr/bin/ffmpeg');
        $transcoder->transcode('/path/to/song.flac', '/path/to/output.m4a', 128, TranscodeCodec::AAC);

        $closure = static function (PendingProcess $process): bool {
            return (
                $process->command === [
                    '/usr/bin/ffmpeg',
                    '-nostdin',
                    '-i',
                    '/path/to/song.flac',
                    '-vn',
                    '-c:a',
                    'aac',
                    '-b:a',
                    '128k',
                    '-aac_coder',
                    'fast',
                    '-threads',
                    '0',
                    '-movflags',
                    '+faststart',
                    '-y',
                    '/path/to/output.m4a',
                ]
            );
        };

        Process::assertRanTimes($closure, 1);
    }

    #[Test]
    public function transcodeWithoutFastAacCoder(): void
    {
        Process::fake();
        File::expects('ensureDirectoryExists')->with('/path/to');

        $transcoder = new Transcoder(transcodeTimeout: 300, ffmpegPath: '/usr/bin/ffmpeg', aacFast: false);
        $transcoder->transcode('/path/to/song.aiff', '/path/to/output.m4a', 320, TranscodeCodec::AAC);

        Process::assertRanTimes(static function (PendingProcess $process): bool {
            return (
                $process->command === [
                    '/usr/bin/ffmpeg',
                    '-nostdin',
                    '-i',
                    '/path/to/song.aiff',
                    '-vn',
                    '-c:a',
                    'aac',
                    '-b:a',
                    '320k',
                    '-threads',
                    '0',
                    '-movflags',
                    '+faststart',
                    '-y',
                    '/path/to/output.m4a',
                ]
            );
        }, 1);
    }

    #[Test]
    public function transcodeToOpusWebm(): void
    {
        Process::fake();
        File::expects('ensureDirectoryExists')->with('/path/to');

        $transcoder = new Transcoder(transcodeTimeout: 300, ffmpegPath: '/usr/bin/ffmpeg');
        $transcoder->transcode('/path/to/song.aiff', '/path/to/output.weba', 256, TranscodeCodec::OPUS);

        Process::assertRanTimes(static function (PendingProcess $process): bool {
            return (
                $process->command === [
                    '/usr/bin/ffmpeg',
                    '-nostdin',
                    '-i',
                    '/path/to/song.aiff',
                    '-vn',
                    '-c:a',
                    'libopus',
                    '-b:a',
                    '256k',
                    '-f',
                    'webm',
                    '-y',
                    '/path/to/output.weba',
                ]
            );
        }, 1);
    }

    #[Test]
    public function throwOnFailure(): void
    {
        Process::fake([
            '*' => Process::result(exitCode: 1, errorOutput: 'something went wrong'),
        ]);

        File::expects('ensureDirectoryExists')->with('/path/to');

        $this->expectException(TranscodingFailedException::class);
        $this->expectExceptionMessage('something went wrong');

        $transcoder = new Transcoder(transcodeTimeout: 300, ffmpegPath: '/usr/bin/ffmpeg');
        $transcoder->transcode('/path/to/song.flac', '/path/to/output.m4a', 128, TranscodeCodec::AAC);
    }

    #[Test]
    public function respectsConfiguredTimeout(): void
    {
        Process::fake();
        File::expects('ensureDirectoryExists')->with('/path/to');

        $transcoder = new Transcoder(transcodeTimeout: 600, ffmpegPath: '/usr/bin/ffmpeg');
        $transcoder->transcode('/path/to/song.flac', '/path/to/output.m4a', 128, TranscodeCodec::AAC);

        Process::assertRanTimes(static function (PendingProcess $process): bool {
            return $process->timeout === 600;
        }, 1);
    }

    #[Test]
    public function disablesTimeoutWhenZero(): void
    {
        Process::fake();
        File::expects('ensureDirectoryExists')->with('/path/to');

        $transcoder = new Transcoder(transcodeTimeout: 0, ffmpegPath: '/usr/bin/ffmpeg');
        $transcoder->transcode('/path/to/song.flac', '/path/to/output.m4a', 128, TranscodeCodec::AAC);

        Process::assertRanTimes(static function (PendingProcess $process): bool {
            return $process->timeout === null;
        }, 1);
    }

    #[Test]
    public function supportsAacWithoutProbing(): void
    {
        Process::fake();

        $transcoder = new Transcoder(ffmpegPath: '/usr/bin/ffmpeg');

        self::assertTrue($transcoder->supports(TranscodeCodec::AAC));

        Process::assertNothingRan();
    }

    #[Test]
    public function supportsOpusWhenFfmpegHasLibopusEncoderAndCachesTheProbe(): void
    {
        Cache::flush();
        Process::fake(['*' => Process::result(output: 'Encoder libopus [libopus Opus]:')]);

        $transcoder = new Transcoder(ffmpegPath: PHP_BINARY);

        self::assertTrue($transcoder->supports(TranscodeCodec::OPUS));
        self::assertTrue($transcoder->supports(TranscodeCodec::OPUS));

        Process::assertRanTimes(static function (PendingProcess $process): bool {
            return in_array('encoder=libopus', $process->command, true);
        }, 1);
    }

    #[Test]
    public function rejectsOpusWithoutValidFfmpeg(): void
    {
        Process::fake();

        $transcoder = new Transcoder(ffmpegPath: '/nonexistent/ffmpeg');

        self::assertFalse($transcoder->supports(TranscodeCodec::OPUS));

        Process::assertNothingRan();
    }

    #[Test]
    public function rejectsOpusWhenFfmpegLacksLibopusEncoder(): void
    {
        Cache::flush();
        Process::fake(['*' => Process::result(output: "Codec 'libopus' is not recognized by FFmpeg.")]);

        $transcoder = new Transcoder(ffmpegPath: PHP_BINARY);

        self::assertFalse($transcoder->supports(TranscodeCodec::OPUS));
    }

    #[Test]
    public function rejectsOpusAndLogsWhenTheProbeFails(): void
    {
        Cache::flush();
        Process::fake();
        File::expects('lastModified')->with(PHP_BINARY)->andThrow(new RuntimeException('something went wrong'));
        Log::expects('warning')->withArgs(
            static fn (string $message): bool => str_contains($message, 'Falling back to AAC'),
        );

        $transcoder = new Transcoder(ffmpegPath: PHP_BINARY);

        self::assertFalse($transcoder->supports(TranscodeCodec::OPUS));
    }

    #[Test]
    public function prefersConfiguredCodecWhenSupported(): void
    {
        Cache::flush();
        Process::fake(['*' => Process::result(output: 'Encoder libopus [libopus Opus]:')]);

        $transcoder = new Transcoder(ffmpegPath: PHP_BINARY, configuredCodec: TranscodeCodec::OPUS);

        self::assertSame(TranscodeCodec::OPUS, $transcoder->preferredCodec());
    }

    #[Test]
    public function fallsBackToDefaultCodecWhenConfiguredCodecIsUnsupported(): void
    {
        Cache::flush();
        Process::fake(['*' => Process::result(output: "Codec 'libopus' is not recognized by FFmpeg.")]);

        $transcoder = new Transcoder(ffmpegPath: PHP_BINARY, configuredCodec: TranscodeCodec::OPUS);

        self::assertSame(TranscodeCodec::AAC, $transcoder->preferredCodec());
    }
}
