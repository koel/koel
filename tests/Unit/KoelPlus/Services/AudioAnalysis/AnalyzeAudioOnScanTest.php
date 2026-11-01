<?php

namespace Tests\Unit\KoelPlus\Services\AudioAnalysis;

use App\Facades\Dispatcher;
use App\Jobs\AnalyzeSongAudioJob;
use App\Models\Song;
use App\Services\AudioAnalysis\AnalyzeAudioOnScan;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\PlusTestCase;

class AnalyzeAudioOnScanTest extends PlusTestCase
{
    #[Test]
    public function queueTheAnalysis(): void
    {
        $song = Song::factory()->createOne();

        Dispatcher::expects('dispatch')->with(Mockery::type(AnalyzeSongAudioJob::class));

        (new AnalyzeAudioOnScan(enabled: true, ffmpegPath: PHP_BINARY))->analyzeSongIfEnabled($song);
    }

    #[Test]
    public function queueTheAnalysisOnTheConfiguredQueue(): void
    {
        $song = Song::factory()->createOne();

        Dispatcher::expects('dispatch')->with(Mockery::on(
            static fn (AnalyzeSongAudioJob $job): bool => $job->queue === 'analysis',
        ));

        (new AnalyzeAudioOnScan(enabled: true, ffmpegPath: PHP_BINARY, queue: 'analysis'))->analyzeSongIfEnabled($song);
    }

    #[Test]
    public function doNothingWhenTurnedOff(): void
    {
        $song = Song::factory()->createOne();

        Dispatcher::expects('dispatch')->never();

        (new AnalyzeAudioOnScan())->analyzeSongIfEnabled($song);
    }

    #[Test]
    public function keepTheScanGoingWhenAnInlineJobFails(): void
    {
        $song = Song::factory()->createOne();

        Dispatcher::expects('dispatch')->andThrow(new RuntimeException('ffmpeg failed'));

        (new AnalyzeAudioOnScan(enabled: true, ffmpegPath: PHP_BINARY))->analyzeSongIfEnabled($song);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function doNothingWithoutFfmpeg(): void
    {
        $song = Song::factory()->createOne();

        Dispatcher::expects('dispatch')->never();

        (new AnalyzeAudioOnScan(enabled: true, ffmpegPath: '/nonexistent/ffmpeg'))->analyzeSongIfEnabled($song);
    }
}
