<?php

namespace App\Services\AudioAnalysis;

use App\Facades\Dispatcher;
use App\Facades\License;
use App\Jobs\AnalyzeSongAudioJob;
use App\Models\Song;
use App\Services\SongStorages\Contracts\MustDeleteTemporaryLocalFileAfterUpload;
use App\Services\SongStorages\SongStorage;
use Illuminate\Container\Attributes\Config;

class AnalyzeAudioOnScan
{
    public function __construct(
        private readonly SongStorage $storage,
        private readonly AudioAnalyzer $analyzer,
        #[Config('koel.scan.analyze_audio')]
        private readonly bool $enabled = false,
    ) {}

    public function analyzeSongIfEnabled(Song $song, string $localFilePath): void
    {
        if (!$this->enabled || License::isCommunity()) {
            return;
        }

        if ($this->storage instanceof MustDeleteTemporaryLocalFileAfterUpload) {
            rescue(fn () => $this->analyzer->analyzeSong($song, $localFilePath));

            return;
        }

        rescue(static fn (): mixed => Dispatcher::dispatch(new AnalyzeSongAudioJob($song)));
    }
}
