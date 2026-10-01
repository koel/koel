<?php

namespace App\Jobs;

use App\Models\Song;
use App\Services\Transcoding\TranscodeStrategyFactory;

class TranscodeSongJob extends QueuedJob
{
    public function __construct(
        private readonly Song $song,
        private readonly int $bitRate,
    ) {}

    public function handle(): void
    {
        TranscodeStrategyFactory::make($this->song->storage)->getTranscodeLocation($this->song, $this->bitRate);
    }
}
