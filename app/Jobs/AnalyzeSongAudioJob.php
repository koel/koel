<?php

namespace App\Jobs;

use App\Models\Song;
use App\Services\AudioAnalysis\AudioAnalyzer;
use App\Services\SongStorages\Contracts\MustDeleteTemporaryLocalFileAfterUpload;
use App\Services\SongStorages\SongStorageFactory;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Support\Facades\File;

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
        $storage = SongStorageFactory::make($this->song->storage);
        $localPath = $storage->getLocalPath($this->song->path);

        try {
            $analyzer->analyzeSong($this->song, $localPath);
        } finally {
            if ($storage instanceof MustDeleteTemporaryLocalFileAfterUpload) {
                File::delete($localPath);
            }
        }
    }
}
