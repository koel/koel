<?php

namespace Tests\Unit\Services\Transcoding;

use App\Enums\SongStorageType;
use App\Facades\Dispatcher;
use App\Jobs\TranscodeSongJob;
use App\Models\Song;
use App\Services\Transcoding\CloudTranscodingStrategy;
use App\Services\Transcoding\TranscodeOnScan;
use Mockery;
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

        (new TranscodeOnScan(enabled: true, bitRate: 256))->transcodeSongIfNeeded(
            $song,
            '/tmp/upload/song.flac',
            localFileIsTemporary: true,
        );
    }

    #[Test]
    public function leaveAFileThatStaysOnDiskToAJob(): void
    {
        $song = self::createFlacSong(SongStorageType::LOCAL);

        Dispatcher::expects('dispatch')->with(Mockery::type(TranscodeSongJob::class));

        (new TranscodeOnScan(enabled: true, bitRate: 256))->transcodeSongIfNeeded(
            $song,
            '/music/song.flac',
            localFileIsTemporary: false,
        );
    }

    #[Test]
    public function doNothingWhenTurnedOff(): void
    {
        $song = self::createFlacSong(SongStorageType::LOCAL);

        Dispatcher::expects('dispatch')->never();

        (new TranscodeOnScan(enabled: false))->transcodeSongIfNeeded(
            $song,
            '/music/song.flac',
            localFileIsTemporary: false,
        );
    }

    #[Test]
    public function doNothingForASongThatNeedsNoTranscoding(): void
    {
        $song = Song::factory()->createOne(['mime_type' => 'audio/mpeg', 'storage' => SongStorageType::LOCAL]);

        Dispatcher::expects('dispatch')->never();

        (new TranscodeOnScan(enabled: true))->transcodeSongIfNeeded(
            $song,
            '/music/song.mp3',
            localFileIsTemporary: false,
        );
    }

    #[Test]
    public function keepTheScanGoingWhenTheTranscodeFails(): void
    {
        $song = self::createFlacSong(SongStorageType::S3);

        $this
            ->mock(CloudTranscodingStrategy::class)
            ->expects('getTranscodeLocation')
            ->andThrow(new \RuntimeException('ffmpeg failed'));

        (new TranscodeOnScan(enabled: true))->transcodeSongIfNeeded(
            $song,
            '/tmp/upload/song.flac',
            localFileIsTemporary: true,
        );

        $this->addToAssertionCount(1);
    }
}
