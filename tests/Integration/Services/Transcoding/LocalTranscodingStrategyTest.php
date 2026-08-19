<?php

namespace Tests\Integration\Services\Transcoding;

use App\Enums\TranscodeCodec;
use App\Helpers\Ulid;
use App\Models\Song;
use App\Models\Transcode;
use App\Services\Transcoding\LocalTranscodingStrategy;
use App\Services\Transcoding\OpusTranscodeCoordinator;
use App\Services\Transcoding\Transcoder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Sleep;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class LocalTranscodingStrategyTest extends TestCase
{
    private LocalTranscodingStrategy $strategy;
    private MockInterface|Transcoder $transcoder;

    public function setUp(): void
    {
        parent::setUp();

        $this->transcoder = $this->mock(Transcoder::class);
        $this->strategy = app(LocalTranscodingStrategy::class);
    }

    #[Test]
    public function getTranscodedLocation(): void
    {
        $song = Song::factory()->createOne(['path' => '/path/to/song.flac']);

        $ulid = Ulid::freeze();

        $destination = artifact_path("transcodes/128/$ulid.m4a", ensureDirectoryExists: false);

        $this->transcoder->expects('preferredCodec')->andReturn(TranscodeCodec::AAC);
        $this->transcoder->expects('transcode')->with('/path/to/song.flac', $destination, 128, TranscodeCodec::AAC);

        File::expects('hash')->with($destination)->andReturn('mocked-checksum');
        File::expects('ensureDirectoryExists')->with(dirname($destination));
        File::expects('size')->with($destination)->andReturn(1_024);

        $transcodedPath = $this->strategy->getTranscodeLocation($song, 128);

        $this->assertDatabaseHas(Transcode::class, [
            'song_id' => $song->id,
            'location' => $destination,
            'bit_rate' => 128,
            'hash' => 'mocked-checksum',
            'file_size' => 1_024,
        ]);

        self::assertSame($transcodedPath, $destination);
    }

    #[Test]
    public function createNewTranscodeWithPreferredCodec(): void
    {
        $song = Song::factory()->createOne(['path' => '/path/to/song.aiff']);
        $ulid = Ulid::freeze();
        $destination = artifact_path("transcodes/256/$ulid.weba", ensureDirectoryExists: false);

        $this->transcoder->expects('preferredCodec')->andReturn(TranscodeCodec::OPUS);
        $this->transcoder->expects('transcode')->with('/path/to/song.aiff', $destination, 256, TranscodeCodec::OPUS);

        File::expects('hash')->with($destination)->andReturn('mocked-checksum');
        File::expects('ensureDirectoryExists')->with(dirname($destination));
        File::expects('size')->with($destination)->andReturn(1_024);

        $transcodedPath = $this->strategy->getTranscodeLocation($song, 256);

        $this->assertDatabaseHas(Transcode::class, [
            'song_id' => $song->id,
            'location' => $destination,
            'bit_rate' => 256,
            'codec' => TranscodeCodec::OPUS->value,
            'hash' => 'mocked-checksum',
            'file_size' => 1_024,
        ]);

        self::assertSame($destination, $transcodedPath);
    }

    #[Test]
    public function reuseExistingTranscodeRegardlessOfConfiguredCodec(): void
    {
        $this->transcoder->expects('preferredCodec')->never();
        $this->transcoder->expects('transcode')->never();

        $song = Song::factory()->createOne(['path' => '/path/to/song.aiff']);
        $transcode = Transcode::factory()->for($song)->createOne([
            'location' => '/path/to/transcode.m4a',
            'bit_rate' => 256,
            'codec' => TranscodeCodec::AAC,
            'hash' => 'mocked-checksum',
        ]);

        File::expects('isReadable')->with('/path/to/transcode.m4a')->andReturn(true);
        File::expects('hash')->with('/path/to/transcode.m4a')->andReturn('mocked-checksum');

        $transcodedPath = $this->strategy->getTranscodeLocation($song, 256);

        self::assertSame($transcode->location, $transcodedPath);
    }

    #[Test]
    public function normalOpusRequestWaitsForProgressiveCacheOwner(): void
    {
        $song = Song::factory()->createOne(['path' => '/path/to/song.aiff']);
        $this->transcoder->allows('preferredCodec')->andReturn(TranscodeCodec::OPUS);
        $winnerLocation = '/path/to/progressive-transcode.weba';
        $progressiveLock = app(OpusTranscodeCoordinator::class)->acquire($song, 256);
        Sleep::fake();
        Sleep::whenFakingSleep(static function () use ($song, $winnerLocation, $progressiveLock): void {
            Transcode::factory()->for($song)->createOne([
                'location' => $winnerLocation,
                'bit_rate' => 256,
                'codec' => TranscodeCodec::OPUS,
                'hash' => 'winner-checksum',
            ]);
            $progressiveLock->release();
        });

        $this->transcoder->expects('transcode')->never();
        File::expects('isReadable')->with($winnerLocation)->andReturnTrue();
        File::expects('hash')->with($winnerLocation)->andReturn('winner-checksum');

        try {
            $location = $this->strategy->getTranscodeLocation($song, 256);

            self::assertSame($winnerLocation, $location);
            Sleep::assertSleptTimes(1);
        } finally {
            $progressiveLock->release();
            Sleep::fake(false);
        }
    }

    #[Test]
    public function normalOpusRequestBecomesOwnerAfterProgressiveFailure(): void
    {
        $song = Song::factory()->createOne(['path' => '/path/to/song.aiff']);
        $this->transcoder->allows('preferredCodec')->andReturn(TranscodeCodec::OPUS);
        $progressiveLock = app(OpusTranscodeCoordinator::class)->acquire($song, 256);
        Sleep::fake();
        Sleep::whenFakingSleep($progressiveLock->release(...));

        $ulid = Ulid::freeze();
        $destination = artifact_path("transcodes/256/$ulid.weba", ensureDirectoryExists: false);
        $this->transcoder->expects('transcode')->with('/path/to/song.aiff', $destination, 256, TranscodeCodec::OPUS);
        File::expects('ensureDirectoryExists')->with(dirname($destination));
        File::expects('hash')->with($destination)->andReturn('new-checksum');
        File::expects('size')->with($destination)->andReturn(1_024);

        try {
            $location = $this->strategy->getTranscodeLocation($song, 256);

            self::assertSame($destination, $location);
            Sleep::assertSleptTimes(1);
        } finally {
            $progressiveLock->release();
            Sleep::fake(false);
        }
    }

    #[Test]
    public function removesCompletedArtifactWhenTranscodeUpsertFails(): void
    {
        $song = Song::factory()->createOne(['path' => '/path/to/song.aiff']);
        $this->transcoder->allows('preferredCodec')->andReturn(TranscodeCodec::OPUS);
        $ulid = Ulid::freeze();
        $destination = artifact_path("transcodes/256/$ulid.weba", ensureDirectoryExists: false);

        $this->transcoder
            ->expects('transcode')
            ->with('/path/to/song.aiff', $destination, 256, TranscodeCodec::OPUS)
            ->andReturnUsing(static function () use ($destination): void {
                File::put($destination, 'completed-audio');
            });
        $wrappedTranscodesTable = DB::connection()->getQueryGrammar()->wrapTable('transcodes');

        DB::connection()->beforeExecuting(static function (string $query) use ($wrappedTranscodesTable): void {
            throw_if(
                str_contains(strtolower($query), sprintf('insert into %s', strtolower($wrappedTranscodesTable))),
                RuntimeException::class,
                'transcode upsert unavailable',
            );
        });

        $caughtException = null;

        try {
            try {
                $this->strategy->getTranscodeLocation($song, 256);
            } catch (RuntimeException $e) {
                $caughtException = $e;
            }

            self::assertNotNull($caughtException, 'Expected the transcode upsert to fail.');
            self::assertSame('transcode upsert unavailable', $caughtException->getMessage());
            self::assertFalse(File::exists($destination));
        } finally {
            File::delete($destination);
        }
    }

    #[Test]
    public function removesPartialArtifactWhenTranscodingFails(): void
    {
        $song = Song::factory()->createOne(['path' => '/path/to/song.aiff']);
        $this->transcoder->allows('preferredCodec')->andReturn(TranscodeCodec::OPUS);
        $ulid = Ulid::freeze();
        $destination = artifact_path("transcodes/256/$ulid.weba", ensureDirectoryExists: false);

        $this->transcoder
            ->expects('transcode')
            ->with('/path/to/song.aiff', $destination, 256, TranscodeCodec::OPUS)
            ->andReturnUsing(static function () use ($destination): void {
                File::put($destination, 'partial-audio');

                throw new RuntimeException('ffmpeg failed');
            });

        $caughtException = null;

        try {
            try {
                $this->strategy->getTranscodeLocation($song, 256);
            } catch (RuntimeException $e) {
                $caughtException = $e;
            }

            self::assertNotNull($caughtException, 'Expected transcoding to fail.');
            self::assertSame('ffmpeg failed', $caughtException->getMessage());
            self::assertFalse(File::exists($destination));
        } finally {
            File::delete($destination);
        }
    }

    #[Test]
    public function getFromDatabaseRecord(): void
    {
        $this->transcoder->expects('transcode')->never();
        $transcode = Transcode::factory()->createOne([
            'location' => '/path/to/transcode.m4a',
            'bit_rate' => 128,
            'hash' => 'mocked-checksum',
        ]);

        File::expects('isReadable')->with('/path/to/transcode.m4a')->andReturn(true);

        File::expects('hash')->with('/path/to/transcode.m4a')->andReturn('mocked-checksum');

        $transcodedPath = $this->strategy->getTranscodeLocation($transcode->song, $transcode->bit_rate);

        self::assertSame($transcode->location, $transcodedPath);
    }

    #[Test]
    public function getOpusFromDatabaseRecord(): void
    {
        $this->transcoder->expects('transcode')->never();
        $transcode = Transcode::factory()->createOne([
            'location' => '/path/to/transcode.weba',
            'bit_rate' => 256,
            'codec' => TranscodeCodec::OPUS,
            'hash' => 'mocked-checksum',
        ]);

        File::expects('isReadable')->with('/path/to/transcode.weba')->andReturn(true);
        File::expects('hash')->with('/path/to/transcode.weba')->andReturn('mocked-checksum');

        $transcodedPath = $this->strategy->getTranscodeLocation($transcode->song, 256);

        self::assertSame($transcode->location, $transcodedPath);
    }

    #[Test]
    public function publishCompletedProgressiveTranscodeAtomically(): void
    {
        $song = Song::factory()->createOne();
        $ulid = Ulid::freeze();
        $destination = artifact_path("transcodes/256/$ulid.weba", ensureDirectoryExists: false);

        File::expects('ensureDirectoryExists')->with(dirname($destination));
        File::expects('move')->with('/tmp/indexed.weba', $destination)->andReturn(true);
        File::expects('hash')->with($destination)->andReturn('mocked-checksum');
        File::expects('size')->with($destination)->andReturn(2_048);

        $location = $this->strategy->publishCompletedTranscode($song, '/tmp/indexed.weba', 256, TranscodeCodec::OPUS);

        self::assertSame($destination, $location);
        $this->assertDatabaseHas(Transcode::class, [
            'song_id' => $song->id,
            'location' => $destination,
            'bit_rate' => 256,
            'codec' => TranscodeCodec::OPUS->value,
            'hash' => 'mocked-checksum',
            'file_size' => 2_048,
        ]);
    }

    #[Test]
    public function discardsProgressiveResultWhenAnotherProducerAlreadyPublished(): void
    {
        $song = Song::factory()->createOne();
        $winnerLocation = '/path/to/winner.weba';
        Transcode::factory()->for($song)->createOne([
            'location' => $winnerLocation,
            'bit_rate' => 256,
            'codec' => TranscodeCodec::OPUS,
            'hash' => 'winner-checksum',
        ]);

        File::expects('isReadable')->with($winnerLocation)->andReturnTrue();
        File::expects('hash')->with($winnerLocation)->andReturn('winner-checksum');
        File::expects('move')->never();

        $location = $this->strategy->publishCompletedTranscode(
            $song,
            '/tmp/losing-progressive.weba',
            256,
            TranscodeCodec::OPUS,
        );

        self::assertSame($winnerLocation, $location);
    }

    #[Test]
    public function removeInvalidCompletedTranscodeBeforeProgressiveReplacement(): void
    {
        $transcode = Transcode::factory()->createOne([
            'location' => '/path/to/invalid.weba',
            'bit_rate' => 256,
            'codec' => TranscodeCodec::OPUS,
            'hash' => 'old-checksum',
        ]);

        File::expects('isReadable')->with('/path/to/invalid.weba')->andReturn(false);
        File::expects('delete')->with('/path/to/invalid.weba');

        self::assertNull($this->strategy->getExistingTranscodeLocation($transcode->song, 256));
    }

    #[Test]
    public function retranscodeIfRecordIsInvalid(): void
    {
        $song = Song::factory()->createOne(['path' => '/path/to/song.flac']);

        $ulid = Ulid::freeze();
        $transcode = Transcode::factory()->for($song)->createOne([
            'location' => '/path/to/transcode.m4a',
            'bit_rate' => 128,
            'hash' => 'mocked-checksum',
        ]);

        $destination = artifact_path("transcodes/128/$ulid.m4a", ensureDirectoryExists: false);

        File::expects('isReadable')->with('/path/to/transcode.m4a')->andReturn(false);
        File::expects('delete')->with('/path/to/transcode.m4a');
        File::expects('hash')->with($destination)->andReturn('mocked-checksum');
        File::expects('ensureDirectoryExists')->with(dirname($destination));
        File::expects('size')->with($destination)->andReturn(1_024);

        $this->transcoder->expects('preferredCodec')->andReturn(TranscodeCodec::AAC);
        $this->transcoder->expects('transcode')->with('/path/to/song.flac', $destination, 128, TranscodeCodec::AAC);

        $transcodedLocation = $this->strategy->getTranscodeLocation($song, 128);

        self::assertSame($destination, $transcodedLocation);
        self::assertSame($transcode->refresh()->location, $transcodedLocation);
    }
}
