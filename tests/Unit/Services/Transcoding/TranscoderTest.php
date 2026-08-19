<?php

namespace Tests\Unit\Services\Transcoding;

use App\Enums\TranscodeCodec;
use App\Exceptions\ClientDisconnectedException;
use App\Exceptions\TranscodingFailedException;
use App\Services\Transcoding\Transcoder;
use Illuminate\Process\FakeProcessResult;
use Illuminate\Process\InvokedProcess;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Mockery;
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
    public function streamProgressivelyFromRequestedOffset(): void
    {
        Process::fake([
            '*' => Process::describe()->output(['webm-header', 'opus-audio'])->runsFor(2),
        ]);

        $chunks = [];
        $events = [];
        $transcoder = new Transcoder(transcodeTimeout: 300, ffmpegPath: '/usr/bin/ffmpeg');
        $transcoder->streamProgressively('/path/to/song.aiff', 256, 145.5, static function (string $chunk) use (
            &$chunks,
            &$events,
        ): void {
            $chunks[] = $chunk;
            $events[] = 'chunk';
        });
        $events[] = 'complete';

        self::assertSame(["webm-header\n", "opus-audio\n"], $chunks);
        self::assertSame(['chunk', 'chunk', 'complete'], $events);
        Process::assertRanTimes(static function (PendingProcess $process): bool {
            return (
                $process->command === [
                    '/usr/bin/ffmpeg',
                    '-hide_banner',
                    '-loglevel',
                    'error',
                    '-nostdin',
                    '-ss',
                    '145.5',
                    '-i',
                    '/path/to/song.aiff',
                    '-map',
                    '0:a:0',
                    '-vn',
                    '-c:a',
                    'libopus',
                    '-application',
                    'audio',
                    '-b:a',
                    '256k',
                    '-output_ts_offset',
                    '145.5',
                    '-f',
                    'webm',
                    '-live',
                    '1',
                    '-cluster_time_limit',
                    '1000',
                    '-flush_packets',
                    '1',
                    'pipe:1',
                ]
                && $process->quietly
            );
        }, 1);
    }

    #[Test]
    public function progressiveTranscodingReportsStderrWithoutBufferingStdout(): void
    {
        Process::fake([
            '*' => Process::describe()->errorOutput('invalid audio')->exitCode(1),
        ]);

        $this->expectException(TranscodingFailedException::class);
        $this->expectExceptionMessage('invalid audio');

        (new Transcoder(transcodeTimeout: 300, ffmpegPath: '/usr/bin/ffmpeg'))->streamProgressively(
            '/path/to/song.aiff',
            128,
            0,
            static function (string $chunk): void {},
        );
    }

    #[Test]
    public function stopsAndReapsProgressiveProcessWhenChunkConsumerFails(): void
    {
        $pendingProcess = Mockery::mock(PendingProcess::class);
        $invokedProcess = Mockery::mock(InvokedProcess::class);

        Process::expects('timeout')->with(300)->andReturn($pendingProcess);
        $pendingProcess->expects('quietly')->andReturnSelf();
        $pendingProcess
            ->expects('start')
            ->withArgs(static fn (array $command, callable $handler): bool => true)
            ->andReturn($invokedProcess);
        $invokedProcess
            ->expects('waitUntil')
            ->andReturnUsing(static function (callable $handler): FakeProcessResult {
                $handler('out', 'audio');

                return new FakeProcessResult();
            });
        $invokedProcess->expects('stop')->with(1)->once();

        $this->expectException(ClientDisconnectedException::class);

        (new Transcoder(transcodeTimeout: 300, ffmpegPath: '/usr/bin/ffmpeg'))->streamProgressively(
            '/path/to/song.aiff',
            256,
            0,
            static fn () => throw new ClientDisconnectedException(),
        );
    }

    #[Test]
    public function preservesChunkConsumerFailureWithFakeProcess(): void
    {
        Process::fake([
            '*' => Process::describe()->output('audio')->runsFor(2),
        ]);

        $this->expectException(ClientDisconnectedException::class);

        (new Transcoder(transcodeTimeout: 300, ffmpegPath: '/usr/bin/ffmpeg'))->streamProgressively(
            '/path/to/song.aiff',
            256,
            0,
            static fn () => throw new ClientDisconnectedException(),
        );
    }

    #[Test]
    public function streamProgressivelyFromBeginningWithoutSeekArguments(): void
    {
        Process::fake();

        (new Transcoder(transcodeTimeout: 0, ffmpegPath: '/usr/bin/ffmpeg'))->streamProgressively(
            '/path/to/song.aiff',
            128,
            0.0,
            static function (string $chunk): void {},
        );

        Process::assertRanTimes(static function (PendingProcess $process): bool {
            return (
                !in_array('-ss', $process->command, true)
                && !in_array('-output_ts_offset', $process->command, true)
                && $process->timeout === null
            );
        }, 1);
    }

    #[Test]
    public function finalizeProgressiveTranscodeAsIndexedWebm(): void
    {
        Process::fake();
        File::expects('ensureDirectoryExists')->with('/path/to');

        (new Transcoder(transcodeTimeout: 300, ffmpegPath: '/usr/bin/ffmpeg'))->finalizeProgressiveTranscode(
            '/tmp/capture.live.webm',
            '/path/to/output.weba',
        );

        Process::assertRanTimes(static function (PendingProcess $process): bool {
            return (
                $process->command === [
                    '/usr/bin/ffmpeg',
                    '-hide_banner',
                    '-loglevel',
                    'error',
                    '-nostdin',
                    '-i',
                    '/tmp/capture.live.webm',
                    '-map',
                    '0:a:0',
                    '-c:a',
                    'copy',
                    '-f',
                    'webm',
                    '-y',
                    '/path/to/output.weba',
                ]
            );
        }, 1);
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
