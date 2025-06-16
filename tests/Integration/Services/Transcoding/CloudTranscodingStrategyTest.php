<?php

namespace Tests\Integration\Services\Transcoding;

use App\Enums\SongStorageType;
use App\Helpers\Ulid;
use App\Models\Song;
use App\Models\Transcode;
use App\Services\SongStorages\S3CompatibleStorage;
use App\Services\Transcoding\CloudTranscodingStrategy;
use App\Services\Transcoding\Transcoder;
use Illuminate\Support\Facades\File;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CloudTranscodingStrategyTest extends TestCase
{
    private CloudTranscodingStrategy $strategy;
    private MockInterface|Transcoder $transcoder;

    public function setUp(): void
    {
        parent::setUp();

        $this->transcoder = $this->mock(Transcoder::class);
        $this->strategy = app(CloudTranscodingStrategy::class);
    }

    #[Test]
    public function getTranscodeLocation(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create([
            'path' => 's3://bucket/key.flac',
            'storage' => SongStorageType::S3,
        ]);

        $storage = $this->mock(S3CompatibleStorage::class);

        $ulid = Ulid::freeze();
        $songPresignedUrl = 'https://s3.song.presigned.url/key.flac';
        $tmpDestination = artifact_path("tmp/$ulid.m4a", ensureDirectoryExists: false);
        $transcodeKey = "transcodes/128/$ulid.m4a";
        $transcodePresignedUrl = "https://s3.song.presigned.url/transcodes/128/$ulid.m4a";

        $storage->shouldReceive('getPresignedUrl')
            ->with('key.flac')
            ->once()
            ->andReturn($songPresignedUrl);

        $storage->shouldReceive('getPresignedUrl')
            ->with($transcodeKey)
            ->once()
            ->andReturn($transcodePresignedUrl);

        $storage->shouldReceive('uploadToStorage')
            ->with($transcodeKey, $tmpDestination)
            ->once();

        $this->transcoder
            ->shouldReceive('transcode')
            ->with($songPresignedUrl, $tmpDestination, 128)
            ->once();

        File::shouldReceive('ensureDirectoryExists')
            ->with(dirname($tmpDestination))
            ->once();

        File::shouldReceive('hash')
            ->with($tmpDestination)
            ->andReturn('mocked-checksum');

        File::shouldReceive('delete')
            ->with($tmpDestination)
            ->once();

        $transcodedPath = $this->strategy->getTranscodeLocation($song, 128);

        self::assertSame($transcodePresignedUrl, $transcodedPath);

        $this->assertDatabaseHas(Transcode::class, [
            'song_id' => $song->id,
            'location' => $transcodeKey,
            'bit_rate' => 128,
            'hash' => 'mocked-checksum',
        ]);
    }

    #[Test]
    public function getFromDatabaseRecord(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create([
            'path' => 's3://bucket/key.flac',
            'storage' => SongStorageType::S3,
        ]);

        Transcode::factory()->for($song)->create([
            'location' => 'transcodes/128/some-ulid.m4a',
            'bit_rate' => 128,
        ]);

        $storage = $this->mock(S3CompatibleStorage::class);

        $storage->shouldReceive('getPresignedUrl')
            ->with('transcodes/128/some-ulid.m4a')
            ->andReturn('https://s3.song.presigned.url/transcodes/128/some-ulid.m4a');

        $this->transcoder->shouldReceive('transcode')->never();

        self::assertSame(
            'https://s3.song.presigned.url/transcodes/128/some-ulid.m4a',
            $this->strategy->getTranscodeLocation($song, 128)
        );
    }
}
