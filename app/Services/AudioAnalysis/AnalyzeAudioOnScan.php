<?php

namespace App\Services\AudioAnalysis;

use App\Facades\Dispatcher;
use App\Facades\License;
use App\Jobs\AnalyzeSongAudioJob;
use App\Models\Song;
use Illuminate\Container\Attributes\Config;

class AnalyzeAudioOnScan
{
    public function __construct(
        #[Config('koel.scan.analyze_audio')]
        private readonly bool $enabled = false,
        #[Config('koel.streaming.ffmpeg_path')]
        private readonly ?string $ffmpegPath = null,
        #[Config('koel.scan.analysis_queue')]
        private readonly ?string $queue = null,
    ) {}

    public function analyzeSongIfEnabled(Song $song): void
    {
        if (!$this->canAnalyze()) {
            return;
        }

        $job = (new AnalyzeSongAudioJob($song))->onQueue($this->queue);

        rescue(static fn (): mixed => Dispatcher::dispatch($job));
    }

    private function canAnalyze(): bool
    {
        return $this->enabled && License::isPlus() && $this->ffmpegPath && is_executable($this->ffmpegPath);
    }
}
