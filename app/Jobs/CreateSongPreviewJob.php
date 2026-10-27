<?php

namespace App\Jobs;

use App\Models\Song;
use App\Services\Transcoding\TranscodeStrategyFactory;

class CreateSongPreviewJob extends QueuedJob
{
    private const int TIMEOUT_MARGIN_SECONDS = 30;

    public int $timeout;

    /**
     * @param int $transcodeTimeout The longest a transcode may run, 0 for no limit
     */
    public function __construct(
        private readonly Song $song,
        int $transcodeTimeout,
    ) {
        $this->timeout = $transcodeTimeout ? $transcodeTimeout + self::TIMEOUT_MARGIN_SECONDS : 0;
    }

    public function handle(): void
    {
        TranscodeStrategyFactory::make($this->song->storage)->getPreviewLocation($this->song);
    }
}
