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

        $transcoder = new Transcoder(transcodeTimeout: 300);
        $transcoder->transcode('/path/to/song.flac', '/path/to/output.m4a', 128);

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
    public function throwOnFailure(): void
    {
        Process::fake([
            '*' => Process::result(exitCode: 1, errorOutput: 'something went wrong'),
        ]);

        File::expects('ensureDirectoryExists')->with('/path/to');

        $this->expectException(TranscodingFailedException::class);
        $this->expectExceptionMessage('something went wrong');

        $transcoder = new Transcoder(transcodeTimeout: 300);
        $transcoder->transcode('/path/to/song.flac', '/path/to/output.m4a', 128);
    }

    #[Test]
    public function respectsConfiguredTimeout(): void
    {
        Process::fake();
        File::expects('ensureDirectoryExists')->with('/path/to');

        $transcoder = new Transcoder(transcodeTimeout: 600);
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

        $transcoder = new Transcoder(transcodeTimeout: 0);
        $transcoder->transcode('/path/to/song.flac', '/path/to/output.m4a', 128);

        Process::assertRanTimes(static function (PendingProcess $process): bool {
            return $process->timeout === null;
        }, 1);
    }
}
