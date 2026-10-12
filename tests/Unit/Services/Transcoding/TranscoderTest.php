<?php

namespace Tests\Unit\Services\Transcoding;

use App\Exceptions\TranscodingFailedException;
use App\Services\Transcoding\Transcoder;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\Test;
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
        $transcoder->transcode('/path/to/song.flac', '/path/to/output.m4a', 128);

        $closure = static function (PendingProcess $process): bool {
            return (
                $process->command === [
                    '/usr/bin/ffmpeg',
                    '-nostdin',
                    '-i',
                    '/path/to/song.flac',
                    '-vn',
                    '-af',
                    'aformat=sample_rates=7350|8000|11025|12000|16000|22050|24000|32000|44100|48000',
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
        $transcoder->transcode('/path/to/song.aiff', '/path/to/output.m4a', 320);

        Process::assertRanTimes(static function (PendingProcess $process): bool {
            return (
                $process->command === [
                    '/usr/bin/ffmpeg',
                    '-nostdin',
                    '-i',
                    '/path/to/song.aiff',
                    '-vn',
                    '-af',
                    'aformat=sample_rates=7350|8000|11025|12000|16000|22050|24000|32000|44100|48000',
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
    public function throwOnFailure(): void
    {
        Process::fake([
            '*' => Process::result(exitCode: 1, errorOutput: 'something went wrong'),
        ]);

        File::expects('ensureDirectoryExists')->with('/path/to');

        $this->expectException(TranscodingFailedException::class);
        $this->expectExceptionMessage('something went wrong');

        $transcoder = new Transcoder(transcodeTimeout: 300, ffmpegPath: '/usr/bin/ffmpeg');
        $transcoder->transcode('/path/to/song.flac', '/path/to/output.m4a', 128);
    }

    #[Test]
    public function respectsConfiguredTimeout(): void
    {
        Process::fake();
        File::expects('ensureDirectoryExists')->with('/path/to');

        $transcoder = new Transcoder(transcodeTimeout: 600, ffmpegPath: '/usr/bin/ffmpeg');
        $transcoder->transcode('/path/to/song.flac', '/path/to/output.m4a', 128);

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
        $transcoder->transcode('/path/to/song.flac', '/path/to/output.m4a', 128);

        Process::assertRanTimes(static function (PendingProcess $process): bool {
            return $process->timeout === null;
        }, 1);
    }

    #[Test]
    public function useFdkAacWhenFfmpegHasIt(): void
    {
        Process::fake([
            '*-encoders*' => Process::result(output: ' A....D libfdk_aac           Fraunhofer FDK AAC (codec aac)'),
            '*' => Process::result(),
        ]);
        File::expects('ensureDirectoryExists')->with('/path/to');

        $transcoder = new Transcoder(transcodeTimeout: 300, ffmpegPath: '/usr/bin/ffmpeg');
        $transcoder->transcode('/path/to/song.flac', '/path/to/output.m4a', 256);

        Process::assertRan(
            static fn (PendingProcess $process): bool => (
                $process->command === [
                    '/usr/bin/ffmpeg',
                    '-nostdin',
                    '-i',
                    '/path/to/song.flac',
                    '-vn',
                    '-af',
                    'aformat=sample_rates=7350|8000|11025|12000|16000|22050|24000|32000|44100|48000',
                    '-c:a',
                    'libfdk_aac',
                    '-b:a',
                    '256k',
                    '-threads',
                    '0',
                    '-movflags',
                    '+faststart',
                    '-y',
                    '/path/to/output.m4a',
                ]
            ),
        );
    }

    #[Test]
    public function askFfmpegForItsEncodersOnlyOnce(): void
    {
        Process::fake();
        File::expects('ensureDirectoryExists')->twice();

        $transcoder = new Transcoder(transcodeTimeout: 300, ffmpegPath: '/usr/bin/ffmpeg');
        $transcoder->transcode('/path/to/one.flac', '/path/to/one.m4a', 256);
        $transcoder->transcode('/path/to/two.flac', '/path/to/two.m4a', 256);

        Process::assertRanTimes(
            static fn (PendingProcess $process): bool => (
                $process->command === ['/usr/bin/ffmpeg', '-hide_banner', '-encoders']
            ),
            1,
        );
    }
}
