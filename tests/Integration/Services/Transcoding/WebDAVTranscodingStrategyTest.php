<?php

namespace Tests\Integration\Services\Transcoding;

use App\Enums\SongStorageType;
use App\Enums\TranscodeCodec;
use App\Helpers\Ulid;
use App\Models\Song;
use App\Models\Transcode;
use App\Services\SongStorages\WebDAVStorage;
use App\Services\Transcoding\Transcoder;
use App\Services\Transcoding\WebDAVTranscodingStrategy;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebDAVTranscodingStrategyTest extends TestCase
{
    #[Test]
    public function getOpusTranscodeLocation(): void
    {
        $song = Song::factory()->createOne([
            'path' => 'webdav://remote/path/to/song.aiff',
            'storage' => SongStorageType::WEBDAV,
        ]);
        $ulid = Ulid::freeze();
        $destination = artifact_path("transcodes/256/$ulid.weba", ensureDirectoryExists: false);

        $storage = $this->mock(WebDAVStorage::class);
        $storage->expects('copyToLocal')->with('remote/path/to/song.aiff')->andReturn('/tmp/song.aiff');

        File::expects('ensureDirectoryExists')->with(dirname($destination));
        File::expects('hash')->with($destination)->andReturn('mocked-checksum');
        File::expects('size')->with($destination)->andReturn(1_024);
        File::expects('delete')->with('/tmp/song.aiff');

        $transcoder = $this->mock(Transcoder::class);
        $transcoder->expects('preferredCodec')->andReturn(TranscodeCodec::OPUS);
        $transcoder->expects('transcode')->with('/tmp/song.aiff', $destination, 256, TranscodeCodec::OPUS);

        $transcodedPath = app(WebDAVTranscodingStrategy::class)->getTranscodeLocation($song, 256);

        self::assertSame($destination, $transcodedPath);
        $this->assertDatabaseHas(Transcode::class, [
            'song_id' => $song->id,
            'location' => $destination,
            'bit_rate' => 256,
            'codec' => TranscodeCodec::OPUS->value,
            'hash' => 'mocked-checksum',
            'file_size' => 1_024,
        ]);
    }
}
