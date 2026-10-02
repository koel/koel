<?php

namespace Tests\Unit\Services\AudioAnalysis;

use App\Enums\SongStorageType;
use App\Facades\Dispatcher;
use App\Models\Song;
use App\Services\AudioAnalysis\AnalyzeAudioOnScan;
use App\Services\AudioAnalysis\AudioAnalyzer;
use App\Services\SongStorages\LocalStorage;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnalyzeAudioOnScanTest extends TestCase
{
    #[Test]
    public function doNothingWithoutKoelPlus(): void
    {
        $song = Song::factory()->createOne(['storage' => SongStorageType::LOCAL]);

        $analyzer = Mockery::mock(AudioAnalyzer::class);
        $analyzer->expects('analyzeSong')->never();
        Dispatcher::expects('dispatch')->never();

        (new AnalyzeAudioOnScan(
            app(LocalStorage::class),
            $analyzer,
            enabled: true,
            ffmpegPath: PHP_BINARY,
        ))->analyzeSongIfEnabled($song, '/music/song.mp3');
    }
}
