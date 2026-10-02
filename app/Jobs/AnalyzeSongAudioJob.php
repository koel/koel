<?php

namespace App\Jobs;

use App\Models\Song;
use App\Services\AudioAnalysis\AudioAnalyzer;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;

#[DeleteWhenMissingModels]
class AnalyzeSongAudioJob extends QueuedJob
{
    private const int TIMEOUT_MARGIN_SECONDS = 30;

    public int $timeout = AudioAnalyzer::TIMEOUT_SECONDS + self::TIMEOUT_MARGIN_SECONDS;

    public function __construct(
        private readonly Song $song,
    ) {}

    public function handle(AudioAnalyzer $analyzer): void
    {
        $analyzer->analyzeSong($this->song, $this->song->path);
    }
}
