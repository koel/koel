<?php

namespace Tests\Integration\Services\Transcoding;

use App\Enums\SongStorageType;
use App\Helpers\Ulid;
use App\Models\Song;
use App\Models\Transcode;
use App\Services\SongStorages\SftpStorage;
use App\Services\Transcoding\SftpTranscodingStrategy;
use App\Services\Transcoding\Transcoder;
use Illuminate\Support\Facades\File;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SfpTranscodingStrategyTest extends TestCase
{
    private MockInterface|Transcoder $transcoder;
    private SftpTranscodingStrategy $strategy;

    public function setUp(): void
    {
        parent::setUp();

        $this->transcoder = $this->mock(Transcoder::class);
        $this->strategy = app(SftpTranscodingStrategy::class);
    }

    #[Test]
    public function getTranscodeLocation(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create([
            'path' => 'sftp://remote/path/to/song.flac',
            'storage' => SongStorageType::SFTP,
        ]);

        $ulid = Ulid::freeze();
        $destination = artifact_path("transcodes/128/$ulid.m4a", ensureDirectoryExists: false);

        $storage = $this->mock(SftpStorage::class);
        $storage->expects('copyToLocal')->with('remote/path/to/song.flac')->andReturn('/tmp/song.flac');

        File::expects('ensureDirectoryExists')->with(dirname($destination));

        $this->transcoder->expects('transcode')->with('/tmp/song.flac', $destination, 128);

        File::expects('hash')->with($destination)->andReturn('mocked-checksum');
        File::expects('delete')->with('/tmp/song.flac');

        $this->strategy->getTranscodeLocation($song, 128);

        $this->assertDatabaseHas(Transcode::class, [
            'song_id' => $song->id,
            'location' => $destination,
            'bit_rate' => 128,
            'hash' => 'mocked-checksum',
        ]);
    }

    #[Test]
    public function getFromDatabaseRecord(): void
    {
        $this->transcoder->expects('transcode')->never();

        /** @var Transcode $transcode */
        $transcode = Transcode::factory()->create([
            'location' => '/path/to/transcode.m4a',
            'bit_rate' => 128,
            'hash' => 'mocked-checksum',
        ]);

        File::expects('isReadable')
            ->with('/path/to/transcode.m4a')
            ->andReturn(true);

        File::expects('hash')
            ->with('/path/to/transcode.m4a')
            ->andReturn('mocked-checksum');

        $transcodedPath = $this->strategy->getTranscodeLocation($transcode->song, $transcode->bit_rate);

        self::assertSame($transcode->location, $transcodedPath);
    }

    #[Test]
    public function retranscodeIfRecordIsInvalid(): void
    {
        $song = Song::factory()->create([
            'path' => 'sftp://remote/path/to/song.flac',
            'storage' => SongStorageType::SFTP,
        ]);

        $ulid = Ulid::freeze();

        /** @var Transcode $transcode */
        $transcode = Transcode::factory()->for($song)->create([
            'location' => '/path/to/transcode.m4a',
            'bit_rate' => 128,
            'hash' => 'mocked-checksum',
        ]);

        File::expects('isReadable')->with('/path/to/transcode.m4a')->andReturn(false);
        File::expects('delete')->with('/path/to/transcode.m4a');

        $storage = $this->mock(SftpStorage::class);
        $storage->expects('copyToLocal')->with('remote/path/to/song.flac')->andReturn('/tmp/song.flac');

        $destination = artifact_path("transcodes/128/$ulid.m4a", ensureDirectoryExists: false);

        File::expects('ensureDirectoryExists')->with(dirname($destination));

        $this->transcoder->expects('transcode')->with('/tmp/song.flac', $destination, 128);

        File::expects('hash')->with($destination)->andReturn('mocked-checksum');
        File::expects('delete')->with('/tmp/song.flac');

        $this->strategy->getTranscodeLocation($song, 128);
        self::assertSame($destination, $transcode->refresh()->location);
    }
}
