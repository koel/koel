<?php

namespace Tests\Integration\Services;

use App\Enums\SongStorageType;
use App\Models\Song;
use App\Services\SongStorages\S3CompatibleStorage;
use App\Services\SongStorages\SftpStorage;
use App\Services\Transcoder;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Cache;
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
        File::ensureDirectoryExists(sprintf('%s/koel-transcodes', sys_get_temp_dir()));

        $this->transcoder = app(Transcoder::class);
    }

    #[Test]
    public function getTranscodedPathForLocalSong(): void
    {
        Process::fake();

        /** @var Song $song */
        $song = Song::factory()->create();

        $output = sprintf('%s/koel-transcodes/%s.128.mp3', sys_get_temp_dir(), $song->id);
        File::shouldReceive('isReadable')->with($output)->andReturn(true);
        File::shouldReceive('hash')->with($output)->andReturn('mocked-checksum');
        File::shouldReceive('ensureDirectoryExists');

        $transcodedPath = $this->transcoder->getTranscodedPath($song);

        $closure = static function (PendingProcess $process) use ($song): bool {
            return $process->command === [
                    '/usr/bin/ffmpeg',
                    '-i',
                    $song->storage_metadata->getPath(),
                    '-vn',
                    '-b:a',
                    '128k',
                    '-preset',
                    'ultrafast',
                    '-y',
                    sprintf('%s/koel-transcodes/%s.128.mp3', sys_get_temp_dir(), $song->id),
                ];
        };

        Process::assertRanTimes($closure, 1);

        self::assertSame(sprintf('%s/koel-transcodes/%s.128.mp3', sys_get_temp_dir(), $song->id), $transcodedPath);
        self::assertTrue(Cache::has("transcoded.{$song->id}.128"));

        // If we call it again, it should return the cached path without invoking ffmpeg again.
        $transcodedPath = $this->transcoder->getTranscodedPath($song, 128);
        Process::assertRanTimes($closure, 1);
        self::assertSame(sprintf('%s/koel-transcodes/%s.128.mp3', sys_get_temp_dir(), $song->id), $transcodedPath);
    }

    #[Test]
    public function getTranscodedPathForCloudSong(): void
    {
        Process::fake();

        /** @var Song $song */
        $song = Song::factory()->create([
            'storage' => SongStorageType::S3,
            'path' => 's3://bucket/path/to/song.flac',
        ]);

        $s3Storage = $this->mock(S3CompatibleStorage::class);
        $s3Storage->shouldReceive('getSongPresignedUrl')->with($song)->andReturn('https://r.s3.com/song.flac');

        $output = sprintf('%s/koel-transcodes/%s.128.mp3', sys_get_temp_dir(), $song->id);
        File::shouldReceive('isReadable')->with($output)->andReturn(true);
        File::shouldReceive('hash')->with($output)->andReturn('mocked-checksum');
        File::shouldReceive('ensureDirectoryExists');

        $transcodedPath = $this->transcoder->getTranscodedPath($song);

        $closure = static function (PendingProcess $process) use ($song): bool {
            return $process->command === [
                    '/usr/bin/ffmpeg',
                    '-i',
                    'https://r.s3.com/song.flac',
                    '-vn',
                    '-b:a',
                    '128k',
                    '-preset',
                    'ultrafast',
                    '-y',
                    sprintf('%s/koel-transcodes/%s.128.mp3', sys_get_temp_dir(), $song->id),
                ];
        };

        Process::assertRanTimes($closure, 1);

        self::assertSame(sprintf('%s/koel-transcodes/%s.128.mp3', sys_get_temp_dir(), $song->id), $transcodedPath);
        self::assertTrue(Cache::has("transcoded.{$song->id}.128"));

        // If we call it again, it should return the cached path without invoking ffmpeg again.
        $transcodedPath = $this->transcoder->getTranscodedPath($song, 128);
        Process::assertRanTimes($closure, 1);
        self::assertSame(sprintf('%s/koel-transcodes/%s.128.mp3', sys_get_temp_dir(), $song->id), $transcodedPath);
    }

    #[Test]
    public function getTranscodedPathForSftpSong(): void
    {
        Process::fake();

        /** @var Song $song */
        $song = Song::factory()->create([
            'storage' => SongStorageType::SFTP,
            'path' => '/remote/path/to/song.flac',
        ]);

        $s3Storage = $this->mock(SftpStorage::class);
        $s3Storage->shouldReceive('copyToLocal')->with($song)->andReturn('/tmp/var/song.flac');

        $output = sprintf('%s/koel-transcodes/%s.128.mp3', sys_get_temp_dir(), $song->id);
        File::shouldReceive('isReadable')->with($output)->andReturn(true);
        File::shouldReceive('hash')->with($output)->andReturn('mocked-checksum');
        File::shouldReceive('ensureDirectoryExists');

        $transcodedPath = $this->transcoder->getTranscodedPath($song);

        $closure = static function (PendingProcess $process) use ($song): bool {
            return $process->command === [
                    '/usr/bin/ffmpeg',
                    '-i',
                    '/tmp/var/song.flac',
                    '-vn',
                    '-b:a',
                    '128k',
                    '-preset',
                    'ultrafast',
                    '-y',
                    sprintf('%s/koel-transcodes/%s.128.mp3', sys_get_temp_dir(), $song->id),
                ];
        };

        Process::assertRanTimes($closure, 1);

        self::assertSame(sprintf('%s/koel-transcodes/%s.128.mp3', sys_get_temp_dir(), $song->id), $transcodedPath);
        self::assertTrue(Cache::has("transcoded.{$song->id}.128"));

        // If we call it again, it should return the cached path without invoking ffmpeg again.
        $transcodedPath = $this->transcoder->getTranscodedPath($song, 128);
        Process::assertRanTimes($closure, 1);
        self::assertSame(sprintf('%s/koel-transcodes/%s.128.mp3', sys_get_temp_dir(), $song->id), $transcodedPath);
    }
}
