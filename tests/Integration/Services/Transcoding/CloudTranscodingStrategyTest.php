<?php

namespace Tests\Integration\Services\Transcoding;

use App\Enums\SongStorageType;
use App\Enums\TranscodeCodec;
use App\Helpers\Ulid;
use App\Models\Song;
use App\Models\Transcode;
use App\Services\SongStorages\S3CompatibleStorage;
use App\Services\Transcoding\CloudTranscodingStrategy;
use App\Services\Transcoding\Transcoder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\File;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
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
        $song = Song::factory()->createOne([
            'path' => 's3://bucket/key.flac',
            'storage' => SongStorageType::S3,
        ]);

        $storage = $this->mock(S3CompatibleStorage::class);

        $ulid = Ulid::freeze();
        $songPresignedUrl = 'https://s3.song.presigned.url/key.flac';
        $tmpDestination = artifact_path("tmp/$ulid.m4a", ensureDirectoryExists: false);
        $transcodeKey = "transcodes/128/$ulid.m4a";
        $transcodePresignedUrl = "https://s3.song.presigned.url/transcodes/128/$ulid.m4a";

        $storage->expects('getPresignedUrl')->with('key.flac')->andReturn($songPresignedUrl);
        $storage->expects('getPresignedUrl')->with($transcodeKey)->andReturn($transcodePresignedUrl);
        $storage->expects('uploadToStorage')->with($transcodeKey, $tmpDestination);

        $this->transcoder->expects('preferredCodec')->andReturn(TranscodeCodec::AAC);
        $this->transcoder->expects('transcode')->with($songPresignedUrl, $tmpDestination, 128, TranscodeCodec::AAC);

        File::expects('ensureDirectoryExists')->with(dirname($tmpDestination));
        File::expects('hash')->with($tmpDestination)->andReturn('mocked-checksum');
        File::expects('delete')->with($tmpDestination);
        File::expects('size')->with($tmpDestination)->andReturn(1_024);

        $transcodedPath = $this->strategy->getTranscodeLocation($song, 128);

        self::assertSame($transcodePresignedUrl, $transcodedPath);

        $this->assertDatabaseHas(Transcode::class, [
            'song_id' => $song->id,
            'location' => $transcodeKey,
            'bit_rate' => 128,
            'hash' => 'mocked-checksum',
            'file_size' => 1_024,
        ]);
    }

    #[Test]
    public function getOpusTranscodeLocation(): void
    {
        $song = Song::factory()->createOne([
            'path' => 's3://bucket/key.aiff',
            'storage' => SongStorageType::S3,
        ]);

        $storage = $this->mock(S3CompatibleStorage::class);
        $ulid = Ulid::freeze();
        $songPresignedUrl = 'https://s3.song.presigned.url/key.aiff';
        $tmpDestination = artifact_path("tmp/$ulid.weba", ensureDirectoryExists: false);
        $transcodeKey = "transcodes/256/$ulid.weba";
        $transcodePresignedUrl = "https://s3.song.presigned.url/transcodes/256/$ulid.weba";

        $storage->expects('getPresignedUrl')->with('key.aiff')->andReturn($songPresignedUrl);
        $storage->expects('getPresignedUrl')->with($transcodeKey)->andReturn($transcodePresignedUrl);
        $storage->expects('uploadToStorage')->with($transcodeKey, $tmpDestination);

        $this->transcoder->expects('preferredCodec')->andReturn(TranscodeCodec::OPUS);
        $this->transcoder->expects('transcode')->with($songPresignedUrl, $tmpDestination, 256, TranscodeCodec::OPUS);

        File::expects('ensureDirectoryExists')->with(dirname($tmpDestination));
        File::expects('hash')->with($tmpDestination)->andReturn('mocked-checksum');
        File::expects('delete')->with($tmpDestination);
        File::expects('size')->with($tmpDestination)->andReturn(1_024);

        $transcodedPath = $this->strategy->getTranscodeLocation($song, 256);

        self::assertSame($transcodePresignedUrl, $transcodedPath);

        $this->assertDatabaseHas(Transcode::class, [
            'song_id' => $song->id,
            'location' => $transcodeKey,
            'bit_rate' => 256,
            'codec' => TranscodeCodec::OPUS->value,
            'hash' => 'mocked-checksum',
            'file_size' => 1_024,
        ]);
    }

    #[Test]
    public function deletesTemporaryFileWhenTranscodingFails(): void
    {
        $this->transcoder->allows('preferredCodec')->andReturn(TranscodeCodec::AAC);
        $song = Song::factory()->createOne([
            'path' => 's3://bucket/key.flac',
            'storage' => SongStorageType::S3,
        ]);
        $storage = $this->mock(S3CompatibleStorage::class);
        $ulid = Ulid::freeze();
        $songPresignedUrl = 'https://s3.song.presigned.url/key.flac';
        $tmpDestination = artifact_path("tmp/$ulid.m4a", ensureDirectoryExists: false);

        $storage->expects('getPresignedUrl')->with('key.flac')->andReturn($songPresignedUrl);
        $storage->expects('uploadToStorage')->never();
        $this->transcoder
            ->expects('transcode')
            ->with($songPresignedUrl, $tmpDestination, 128, TranscodeCodec::AAC)
            ->andThrow(new RuntimeException('Transcoding failed.'));
        File::expects('ensureDirectoryExists')->with(dirname($tmpDestination));
        File::expects('delete')->with($tmpDestination);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Transcoding failed.');

        $this->strategy->getTranscodeLocation($song, 128);
    }

    #[Test]
    public function cleansPartialNormalUploadAndPreservesUploadExceptionWhenCleanupFails(): void
    {
        $this->transcoder->allows('preferredCodec')->andReturn(TranscodeCodec::AAC);
        $song = Song::factory()->createOne([
            'path' => 's3://bucket/key.flac',
            'storage' => SongStorageType::S3,
        ]);
        $storage = $this->mock(S3CompatibleStorage::class);
        $ulid = Ulid::freeze();
        $songPresignedUrl = 'https://s3.song.presigned.url/key.flac';
        $tmpDestination = artifact_path("tmp/$ulid.m4a", ensureDirectoryExists: false);
        $transcodeKey = "transcodes/128/$ulid.m4a";
        $uploadException = new RuntimeException('Cloud upload failed.');
        $cleanupException = new RuntimeException('Cloud cleanup failed.');
        Exceptions::fake();

        $storage->expects('getPresignedUrl')->with('key.flac')->andReturn($songPresignedUrl);
        $storage->expects('uploadToStorage')->with($transcodeKey, $tmpDestination)->andThrow($uploadException);
        $storage->expects('deleteFileWithKey')->with($transcodeKey, false)->andThrow($cleanupException);
        $this->transcoder->expects('transcode')->with($songPresignedUrl, $tmpDestination, 128, TranscodeCodec::AAC);
        File::expects('ensureDirectoryExists')->with(dirname($tmpDestination));
        File::expects('delete')->with($tmpDestination);

        try {
            $this->strategy->getTranscodeLocation($song, 128);
            self::fail('Expected the upload to fail.');
        } catch (RuntimeException $thrownException) {
            self::assertSame($uploadException, $thrownException);
        }

        Exceptions::assertReported(
            static fn (RuntimeException $reportedException): bool => $reportedException === $cleanupException,
        );
    }

    #[Test]
    public function deletesUploadedFileAndTemporaryFileWhenDatabaseWriteFails(): void
    {
        $this->transcoder->allows('preferredCodec')->andReturn(TranscodeCodec::AAC);
        $song = Song::factory()->createOne([
            'path' => 's3://bucket/key.flac',
            'storage' => SongStorageType::S3,
        ]);
        $song->id = '00000000-0000-0000-0000-000000000000';
        $storage = $this->mock(S3CompatibleStorage::class);
        $ulid = Ulid::freeze();
        $songPresignedUrl = 'https://s3.song.presigned.url/key.flac';
        $tmpDestination = artifact_path("tmp/$ulid.m4a", ensureDirectoryExists: false);
        $transcodeKey = "transcodes/128/$ulid.m4a";

        $storage->expects('getPresignedUrl')->with('key.flac')->andReturn($songPresignedUrl);
        $storage->expects('uploadToStorage')->with($transcodeKey, $tmpDestination);
        $storage->expects('deleteFileWithKey')->with($transcodeKey, false);
        $this->transcoder->expects('transcode')->with($songPresignedUrl, $tmpDestination, 128, TranscodeCodec::AAC);
        File::expects('ensureDirectoryExists')->with(dirname($tmpDestination));
        File::expects('hash')->with($tmpDestination)->andReturn('mocked-checksum');
        File::expects('size')->with($tmpDestination)->andReturn(1_024);
        File::expects('delete')->with($tmpDestination);

        $this->expectException(QueryException::class);

        $this->strategy->getTranscodeLocation($song, 128);
    }

    #[Test]
    public function getFromDatabaseRecord(): void
    {
        $song = Song::factory()->createOne([
            'path' => 's3://bucket/key.flac',
            'storage' => SongStorageType::S3,
        ]);

        Transcode::factory()->for($song)->createOne([
            'location' => 'transcodes/128/some-ulid.m4a',
            'bit_rate' => 128,
        ]);

        $storage = $this->mock(S3CompatibleStorage::class);

        $storage->expects('fileExists')->with('transcodes/128/some-ulid.m4a')->andReturnTrue();
        $storage
            ->expects('getPresignedUrl')
            ->with('transcodes/128/some-ulid.m4a')
            ->andReturn('https://s3.song.presigned.url/transcodes/128/some-ulid.m4a');

        $this->transcoder->expects('transcode')->never();

        self::assertSame('https://s3.song.presigned.url/transcodes/128/some-ulid.m4a', $this->strategy->getTranscodeLocation(
            $song,
            128,
        ));
    }

    #[Test]
    public function publishCompletedProgressiveTranscodeToCloud(): void
    {
        $song = Song::factory()->createOne([
            'path' => 's3://bucket/key.aiff',
            'storage' => SongStorageType::S3,
        ]);
        $storage = $this->mock(S3CompatibleStorage::class);
        $ulid = Ulid::freeze();
        $key = "transcodes/256/$ulid.weba";
        $presignedUrl = "https://s3.song.presigned.url/$key";

        $storage->expects('uploadToStorage')->with($key, '/tmp/indexed.weba');
        $storage->expects('getPresignedUrl')->with($key)->andReturn($presignedUrl);
        File::expects('hash')->with('/tmp/indexed.weba')->andReturn('mocked-checksum');
        File::expects('size')->with('/tmp/indexed.weba')->andReturn(2_048);

        $location = $this->strategy->publishCompletedTranscode($song, '/tmp/indexed.weba', 256, TranscodeCodec::OPUS);

        self::assertSame($presignedUrl, $location);
        $this->assertDatabaseHas(Transcode::class, [
            'song_id' => $song->id,
            'location' => $key,
            'bit_rate' => 256,
            'codec' => TranscodeCodec::OPUS->value,
            'hash' => 'mocked-checksum',
            'file_size' => 2_048,
        ]);
    }

    #[Test]
    public function preservesPublishedObjectWhenPresignedUrlGenerationFails(): void
    {
        $song = Song::factory()->createOne([
            'path' => 's3://bucket/key.aiff',
            'storage' => SongStorageType::S3,
        ]);
        $storage = $this->mock(S3CompatibleStorage::class);
        $ulid = Ulid::freeze();
        $key = "transcodes/256/$ulid.weba";
        $presignedUrlException = new RuntimeException('Presigned URL generation failed.');

        $storage->expects('uploadToStorage')->with($key, '/tmp/indexed.weba');
        $storage->expects('getPresignedUrl')->with($key)->andThrow($presignedUrlException);
        $storage->expects('deleteFileWithKey')->never();
        File::expects('hash')->with('/tmp/indexed.weba')->andReturn('mocked-checksum');
        File::expects('size')->with('/tmp/indexed.weba')->andReturn(2_048);

        try {
            $this->strategy->publishCompletedTranscode($song, '/tmp/indexed.weba', 256, TranscodeCodec::OPUS);
            self::fail('Expected presigned URL generation to fail.');
        } catch (RuntimeException $thrownException) {
            self::assertSame($presignedUrlException, $thrownException);
        }

        $this->assertDatabaseHas(Transcode::class, [
            'song_id' => $song->id,
            'location' => $key,
            'bit_rate' => 256,
            'codec' => TranscodeCodec::OPUS->value,
        ]);
    }

    #[Test]
    public function cleansPartialProgressiveUploadAndPreservesUploadExceptionWhenCleanupFails(): void
    {
        $song = Song::factory()->createOne([
            'path' => 's3://bucket/key.aiff',
            'storage' => SongStorageType::S3,
        ]);
        $storage = $this->mock(S3CompatibleStorage::class);
        $ulid = Ulid::freeze();
        $key = "transcodes/256/$ulid.weba";
        $uploadException = new RuntimeException('Cloud upload failed.');
        $cleanupException = new RuntimeException('Cloud cleanup failed.');
        Exceptions::fake();

        $storage->expects('uploadToStorage')->with($key, '/tmp/indexed.weba')->andThrow($uploadException);
        $storage->expects('deleteFileWithKey')->with($key, false)->andThrow($cleanupException);

        try {
            $this->strategy->publishCompletedTranscode($song, '/tmp/indexed.weba', 256, TranscodeCodec::OPUS);
            self::fail('Expected the upload to fail.');
        } catch (RuntimeException $thrownException) {
            self::assertSame($uploadException, $thrownException);
        }

        Exceptions::assertReported(
            static fn (RuntimeException $reportedException): bool => $reportedException === $cleanupException,
        );
    }

    #[Test]
    public function discardsProgressiveResultWhenAnotherCloudProducerAlreadyPublished(): void
    {
        $song = Song::factory()->createOne([
            'path' => 's3://bucket/key.aiff',
            'storage' => SongStorageType::S3,
        ]);
        $winnerKey = 'transcodes/256/winner.weba';
        $winnerUrl = "https://s3.song.presigned.url/$winnerKey";
        Transcode::factory()->for($song)->createOne([
            'location' => $winnerKey,
            'bit_rate' => 256,
            'codec' => TranscodeCodec::OPUS,
        ]);
        $storage = $this->mock(S3CompatibleStorage::class);

        $storage->expects('fileExists')->with($winnerKey)->andReturnTrue();
        $storage->expects('getPresignedUrl')->with($winnerKey)->andReturn($winnerUrl);
        $storage->expects('uploadToStorage')->never();

        $location = $this->strategy->publishCompletedTranscode(
            $song,
            '/tmp/losing-progressive.weba',
            256,
            TranscodeCodec::OPUS,
        );

        self::assertSame($winnerUrl, $location);
    }

    #[Test]
    public function publishesProgressiveReplacementWhenDatabaseRecordHasNoCloudObject(): void
    {
        $song = Song::factory()->createOne([
            'path' => 's3://bucket/key.aiff',
            'storage' => SongStorageType::S3,
        ]);
        $missingKey = 'transcodes/256/missing.weba';
        Transcode::factory()->for($song)->createOne([
            'location' => $missingKey,
            'bit_rate' => 256,
            'codec' => TranscodeCodec::OPUS,
        ]);
        $storage = $this->mock(S3CompatibleStorage::class);
        $ulid = Ulid::freeze();
        $replacementKey = "transcodes/256/$ulid.weba";
        $replacementUrl = "https://s3.song.presigned.url/$replacementKey";

        $storage->expects('fileExists')->with($missingKey)->andReturnFalse();
        $storage->expects('getPresignedUrl')->with($missingKey)->never();
        $storage->expects('uploadToStorage')->with($replacementKey, '/tmp/indexed.weba');
        $storage->expects('getPresignedUrl')->with($replacementKey)->andReturn($replacementUrl);
        File::expects('hash')->with('/tmp/indexed.weba')->andReturn('replacement-checksum');
        File::expects('size')->with('/tmp/indexed.weba')->andReturn(2_048);

        $location = $this->strategy->publishCompletedTranscode($song, '/tmp/indexed.weba', 256, TranscodeCodec::OPUS);

        self::assertSame($replacementUrl, $location);
        $this->assertDatabaseHas(Transcode::class, [
            'song_id' => $song->id,
            'location' => $replacementKey,
            'bit_rate' => 256,
            'codec' => TranscodeCodec::OPUS->value,
            'hash' => 'replacement-checksum',
            'file_size' => 2_048,
        ]);
    }
}
