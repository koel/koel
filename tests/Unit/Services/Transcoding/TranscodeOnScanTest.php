<?php

namespace Tests\Unit\Services\Transcoding;

use App\Enums\SongStorageType;
use App\Facades\Dispatcher;
use App\Jobs\TranscodeSongJob;
use App\Models\Song;
use App\Services\SongStorages\Contracts\MustDeleteTemporaryLocalFileAfterUpload;
use App\Services\SongStorages\LocalStorage;
use App\Services\SongStorages\SongStorage;
use App\Services\Transcoding\CloudTranscodingStrategy;
use App\Services\Transcoding\TranscodeOnScan;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class TranscodeOnScanTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config(['koel.streaming.transcode_flac' => true]);
    }

    private static function makeRemoteStorage(): SongStorage|MockInterface
    {
        return Mockery::mock(SongStorage::class . ',' . MustDeleteTemporaryLocalFileAfterUpload::class);
    }

    private static function createFlacSong(SongStorageType $storage): Song
    {
        return Song::factory()->createOne(['mime_type' => 'audio/flac', 'storage' => $storage]);
    }

    #[Test]
    public function transcodeATemporaryCopyOnTheSpot(): void
    {
        $song = self::createFlacSong(SongStorageType::S3);

        $this
            ->mock(CloudTranscodingStrategy::class)
            ->expects('getTranscodeLocation')
            ->with($song, 256, '/tmp/upload/song.flac')
            ->andReturn('https://transcode');

        (new TranscodeOnScan(self::makeRemoteStorage(), enabled: true, bitRate: 256))->transcodeSongIfNeeded(
            $song,
            '/tmp/upload/song.flac',
        );
    }

    #[Test]
    public function leaveAFileThatStaysOnDiskToAJob(): void
    {
        $song = self::createFlacSong(SongStorageType::LOCAL);

        Dispatcher::expects('dispatch')->with(Mockery::type(TranscodeSongJob::class));

        (new TranscodeOnScan(app(LocalStorage::class), enabled: true, bitRate: 256))->transcodeSongIfNeeded(
            $song,
            '/music/song.flac',
        );
    }

    #[Test]
    public function doNothingWhenTurnedOff(): void
    {
        $song = self::createFlacSong(SongStorageType::LOCAL);

        Dispatcher::expects('dispatch')->never();

        (new TranscodeOnScan(app(LocalStorage::class), enabled: false))->transcodeSongIfNeeded(
            $song,
            '/music/song.flac',
        );
    }

    #[Test]
    public function doNothingForASongThatNeedsNoTranscoding(): void
    {
        $song = Song::factory()->createOne(['mime_type' => 'audio/mpeg', 'storage' => SongStorageType::LOCAL]);

        Dispatcher::expects('dispatch')->never();

        (new TranscodeOnScan(app(LocalStorage::class), enabled: true))->transcodeSongIfNeeded($song, '/music/song.mp3');
    }

    #[Test]
    public function keepTheScanGoingWhenTheTranscodeFails(): void
    {
        $song = self::createFlacSong(SongStorageType::S3);

        $this
            ->mock(CloudTranscodingStrategy::class)
            ->expects('getTranscodeLocation')
            ->andThrow(new \RuntimeException('ffmpeg failed'));

        (new TranscodeOnScan(self::makeRemoteStorage(), enabled: true))->transcodeSongIfNeeded(
            $song,
            '/tmp/upload/song.flac',
        );

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function keepTheScanGoingWhenAnInlineJobFails(): void
    {
        $song = self::createFlacSong(SongStorageType::LOCAL);

        Dispatcher::expects('dispatch')->andThrow(new RuntimeException('ffmpeg failed'));

        (new TranscodeOnScan(app(LocalStorage::class), enabled: true))->transcodeSongIfNeeded(
            $song,
            '/music/song.flac',
        );

        $this->addToAssertionCount(1);
    }
}
