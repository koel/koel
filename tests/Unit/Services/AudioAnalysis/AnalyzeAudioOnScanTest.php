<?php

namespace Tests\Unit\Services\AudioAnalysis;

use App\Facades\Dispatcher;
use App\Models\Song;
use App\Services\AudioAnalysis\AnalyzeAudioOnScan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnalyzeAudioOnScanTest extends TestCase
{
    #[Test]
    public function doNothingWithoutKoelPlus(): void
    {
        $song = Song::factory()->createOne();

        Dispatcher::expects('dispatch')->never();

        (new AnalyzeAudioOnScan(enabled: true, ffmpegPath: PHP_BINARY))->analyzeSongIfEnabled($song);
    }
}
