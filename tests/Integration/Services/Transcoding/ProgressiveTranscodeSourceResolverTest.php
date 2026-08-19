<?php

namespace Tests\Integration\Services\Transcoding;

use App\Enums\SongStorageType;
use App\Models\Song;
use App\Services\SongStorages\S3CompatibleStorage;
use App\Services\SongStorages\SftpStorage;
use App\Services\SongStorages\WebDAVStorage;
use App\Services\Transcoding\ProgressiveTranscodeSourceResolver;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProgressiveTranscodeSourceResolverTest extends TestCase
{
    #[Test]
    public function resolveLocalSourceDirectly(): void
    {
        $source = app(ProgressiveTranscodeSourceResolver::class)->resolve(Song::factory()->make([
            'storage' => SongStorageType::LOCAL,
            'path' => '/music/song.aiff',
        ]));

        self::assertSame('/music/song.aiff', $source->path);
        self::assertFalse($source->temporary);
    }

    #[Test]
    public function resolveCloudSourceWithPresignedUrl(): void
    {
        $song = Song::factory()->make([
            'storage' => SongStorageType::S3,
            'path' => 's3://bucket/music/song.aiff',
        ]);
        $this
            ->mock(S3CompatibleStorage::class)
            ->expects('getPresignedUrl')
            ->with('music/song.aiff')
            ->andReturn('https://storage.example/song.aiff');

        $source = app(ProgressiveTranscodeSourceResolver::class)->resolve($song);

        self::assertSame('https://storage.example/song.aiff', $source->path);
        self::assertFalse($source->temporary);
    }

    #[Test]
    public function stageSftpSourceLocally(): void
    {
        $song = Song::factory()->make([
            'storage' => SongStorageType::SFTP,
            'path' => 'sftp://music/song.aiff',
        ]);
        $this->mock(SftpStorage::class)->expects('copyToLocal')->with('music/song.aiff')->andReturn('/tmp/song.aiff');

        $source = app(ProgressiveTranscodeSourceResolver::class)->resolve($song);

        self::assertSame('/tmp/song.aiff', $source->path);
        self::assertTrue($source->temporary);
    }

    #[Test]
    public function stageWebdavSourceLocally(): void
    {
        $song = Song::factory()->make([
            'storage' => SongStorageType::WEBDAV,
            'path' => 'webdav://music/song.aiff',
        ]);
        $this->mock(WebDAVStorage::class)->expects('copyToLocal')->with('music/song.aiff')->andReturn('/tmp/song.aiff');

        $source = app(ProgressiveTranscodeSourceResolver::class)->resolve($song);

        self::assertSame('/tmp/song.aiff', $source->path);
        self::assertTrue($source->temporary);
    }
}
