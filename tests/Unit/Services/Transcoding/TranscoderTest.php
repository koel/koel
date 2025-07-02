<?php

namespace Tests\Unit\Services\Transcoding;

use App\Services\Transcoding\Transcoder;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TranscoderTest extends TestCase
{
    private Transcoder $transcoder;

    public function setUp(): void
    {
        parent::setUp();

        config(['koel.streaming.ffmpeg_path' => '/usr/bin/ffmpeg']);
        $this->transcoder = new Transcoder();
    }

    #[Test]
    public function transcode(): void
    {
        Process::fake();
        File::expects('ensureDirectoryExists')->with('/path/to');

        $this->transcoder->transcode('/path/to/song.flac', '/path/to/output.m4a', 128);

        $closure = static function (PendingProcess $process): bool {
            return $process->command === [
                    '/usr/bin/ffmpeg',
                    '-i', '/path/to/song.flac',
                    '-vn',
                    '-c:a', 'aac',
                    '-b:a', '128k',
                    '-y',
                    '/path/to/output.m4a',
                ];
        };

        Process::assertRanTimes($closure, 1);
    }
}
