<?php

namespace Tests\Unit\Jobs;

use App\Enums\SongStorageType;
use App\Jobs\AnalyzeSongAudioJob;
use App\Models\Song;
use App\Services\AudioAnalysis\AudioAnalyzer;
use App\Services\SongStorages\S3CompatibleStorage;
use Illuminate\Support\Facades\File;
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

    #[Test]
    public function analyzeATemporaryCopyOfARemoteFile(): void
    {
        $song = Song::factory()->createOne([
            'path' => 's3://bucket/song.mp3',
            'storage' => SongStorageType::S3,
        ]);

        $localCopy = artifact_path('tmp/analyze-song-audio-job-test.mp3');
        File::put($localCopy, 'audio');

        $this
            ->mock(S3CompatibleStorage::class)
            ->expects('getLocalPath')
            ->with('s3://bucket/song.mp3')
            ->andReturn($localCopy);

        $analyzer = Mockery::mock(AudioAnalyzer::class);
        $analyzer->expects('analyzeSong')->with($song, $localCopy);

        (new AnalyzeSongAudioJob($song))->handle($analyzer);

        self::assertFileDoesNotExist($localCopy);
    }

    #[Test]
    public function outlastTheLongestAllowedAnalysis(): void
    {
        $song = Song::factory()->createOne();

        self::assertGreaterThan(AudioAnalyzer::TIMEOUT_SECONDS, (new AnalyzeSongAudioJob($song))->timeout);
    }
}
