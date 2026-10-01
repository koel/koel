<?php

namespace Tests\Integration\Services\Transcoding;

use App\Helpers\Ulid;
use App\Models\Song;
use App\Models\Transcode;
use App\Services\Transcoding\LocalTranscodingStrategy;
use App\Services\Transcoding\Transcoder;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Sleep;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
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

        $this->transcoder->expects('transcode')->with('/path/to/song.flac', $destination, 128);

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

        $this->transcoder->expects('transcode')->with('/path/to/song.flac', $destination, 128);

        $transcodedLocation = $this->strategy->getTranscodeLocation($song, 128);

        self::assertSame($destination, $transcodedLocation);
        self::assertSame($transcode->refresh()->location, $transcodedLocation);
    }

    #[Test]
    public function holdTheSongsTranscodeLockWhileTranscoding(): void
    {
        $song = Song::factory()->createOne(['path' => '/path/to/song.flac']);
        $lockKey = cache_key('transcode', $song->id, 128);

        File::expects('ensureDirectoryExists');
        File::expects('hash')->andReturn('mocked-checksum');
        File::expects('size')->andReturn(1_024);

        $this->transcoder
            ->expects('transcode')
            ->andReturnUsing(static function () use ($lockKey): void {
                self::assertFalse(Cache::lock($lockKey, 10)->get());
            });

        $this->strategy->getTranscodeLocation($song, 128);

        self::assertTrue(Cache::lock($lockKey, 10)->get());
    }

    #[Test]
    public function neverTranscodeWithoutTheLock(): void
    {
        $this->freezeTime();
        Sleep::fake(syncWithCarbon: true);

        $song = Song::factory()->createOne(['path' => '/path/to/song.flac']);
        Cache::lock(cache_key('transcode', $song->id, 128), 600)->get();

        $this->transcoder->expects('transcode')->never();
        $this->expectException(LockTimeoutException::class);

        $this->strategy->getTranscodeLocation($song, 128);
    }
}
