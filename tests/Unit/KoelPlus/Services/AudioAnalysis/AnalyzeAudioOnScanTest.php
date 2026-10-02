<?php

namespace Tests\Unit\KoelPlus\Services\AudioAnalysis;

use App\Enums\SongStorageType;
use App\Facades\Dispatcher;
use App\Jobs\AnalyzeSongAudioJob;
use App\Models\Song;
use App\Services\AudioAnalysis\AnalyzeAudioOnScan;
use App\Services\AudioAnalysis\AudioAnalyzer;
use App\Services\SongStorages\Contracts\MustDeleteTemporaryLocalFileAfterUpload;
use App\Services\SongStorages\LocalStorage;
use App\Services\SongStorages\SongStorage;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\PlusTestCase;

class AnalyzeAudioOnScanTest extends PlusTestCase
{
    private AudioAnalyzer|MockInterface $analyzer;

    public function setUp(): void
    {
        parent::setUp();

        $this->analyzer = Mockery::mock(AudioAnalyzer::class);
    }

    private static function makeRemoteStorage(): SongStorage|MockInterface
    {
        return Mockery::mock(SongStorage::class . ',' . MustDeleteTemporaryLocalFileAfterUpload::class);
    }

    #[Test]
    public function analyzeATemporaryCopyOnTheSpot(): void
    {
        $song = Song::factory()->createOne(['storage' => SongStorageType::S3]);

        $this->analyzer->expects('analyzeSong')->with($song, '/tmp/upload/song.mp3');
        Dispatcher::expects('dispatch')->never();

        (new AnalyzeAudioOnScan(self::makeRemoteStorage(), $this->analyzer, enabled: true))->analyzeSongIfEnabled(
            $song,
            '/tmp/upload/song.mp3',
        );
    }

    #[Test]
    public function leaveAFileThatStaysOnDiskToAJob(): void
    {
        $song = Song::factory()->createOne(['storage' => SongStorageType::LOCAL]);

        $this->analyzer->expects('analyzeSong')->never();
        Dispatcher::expects('dispatch')->with(Mockery::type(AnalyzeSongAudioJob::class));

        (new AnalyzeAudioOnScan(app(LocalStorage::class), $this->analyzer, enabled: true))->analyzeSongIfEnabled(
            $song,
            '/music/song.mp3',
        );
    }

    #[Test]
    public function doNothingWhenTurnedOff(): void
    {
        $song = Song::factory()->createOne(['storage' => SongStorageType::S3]);

        $this->analyzer->expects('analyzeSong')->never();
        Dispatcher::expects('dispatch')->never();

        (new AnalyzeAudioOnScan(self::makeRemoteStorage(), $this->analyzer))->analyzeSongIfEnabled(
            $song,
            '/tmp/upload/song.mp3',
        );
    }

    #[Test]
    public function keepTheScanGoingWhenTheAnalysisFails(): void
    {
        $song = Song::factory()->createOne(['storage' => SongStorageType::S3]);

        $this->analyzer->expects('analyzeSong')->andThrow(new RuntimeException('ffmpeg failed'));

        (new AnalyzeAudioOnScan(self::makeRemoteStorage(), $this->analyzer, enabled: true))->analyzeSongIfEnabled(
            $song,
            '/tmp/upload/song.mp3',
        );

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function keepTheScanGoingWhenAnInlineJobFails(): void
    {
        $song = Song::factory()->createOne(['storage' => SongStorageType::LOCAL]);

        Dispatcher::expects('dispatch')->andThrow(new RuntimeException('ffmpeg failed'));

        (new AnalyzeAudioOnScan(app(LocalStorage::class), $this->analyzer, enabled: true))->analyzeSongIfEnabled(
            $song,
            '/music/song.mp3',
        );

        $this->addToAssertionCount(1);
    }
}
