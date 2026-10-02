<?php

namespace Tests\Unit\Jobs;

use App\Jobs\AnalyzeSongAudioJob;
use App\Models\Song;
use App\Services\AudioAnalysis\AudioAnalyzer;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnalyzeSongAudioJobTest extends TestCase
{
    #[Test]
    public function analyzeTheSongFile(): void
    {
        $song = Song::factory()->createOne(['path' => '/music/song.mp3']);

        $analyzer = Mockery::mock(AudioAnalyzer::class);
        $analyzer->expects('analyzeSong')->with($song, '/music/song.mp3');

        (new AnalyzeSongAudioJob($song))->handle($analyzer);
    }
}
