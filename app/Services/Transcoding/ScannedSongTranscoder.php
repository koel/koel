<?php

namespace App\Services\Transcoding;

use App\Facades\Dispatcher;
use App\Jobs\TranscodeSongJob;
use App\Models\Song;
use App\Services\Streamer\Streamer;
use Illuminate\Container\Attributes\Config;

class ScannedSongTranscoder
{
    public function __construct(
        #[Config('koel.streaming.transcode_on_scan')]
        private readonly bool $transcodeOnScan = false,
        #[Config('koel.streaming.bitrate')]
        private readonly int $bitRate = 128,
    ) {}

    /**
     * A temporary local copy (a song uploaded to remote storage) is gone right after the scan, so it is
     * transcoded on the spot to save fetching the song again; a file that stays on disk is left to a job.
     */
    public function transcodeScannedSong(Song $song, string $localFilePath, bool $localFileIsTemporary): void
    {
        if (!$this->transcodeOnScan || !Streamer::shouldTranscode($song)) {
            return;
        }

        if ($localFileIsTemporary) {
            rescue(fn () => TranscodeStrategyFactory::make($song->storage)->getTranscodeLocation(
                $song,
                $this->bitRate,
                $localFilePath,
            ));

            return;
        }

        Dispatcher::dispatch(new TranscodeSongJob($song, $this->bitRate));
    }
}
