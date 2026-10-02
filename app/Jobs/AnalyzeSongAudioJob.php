<?php

namespace App\Jobs;

use App\Models\Song;
use App\Services\AudioAnalysis\AudioAnalyzer;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;

#[DeleteWhenMissingModels]
class AnalyzeSongAudioJob extends QueuedJob
{
    public function __construct(
        private readonly Song $song,
    ) {}

    public function handle(AudioAnalyzer $analyzer): void
    {
        $analyzer->analyzeSong($this->song, $this->song->path);
    }
}
