<?php

namespace App\Services\Transcoding;

use App\Facades\Dispatcher;
use App\Jobs\TranscodeSongJob;
use App\Models\Song;
use App\Services\SongStorages\Contracts\MustDeleteTemporaryLocalFileAfterUpload;
use App\Services\SongStorages\SongStorage;
use App\Services\Streamer\Streamer;
use Illuminate\Container\Attributes\Config;

class TranscodeOnScan
{
    public function __construct(
        private readonly SongStorage $storage,
        #[Config('koel.streaming.transcode_on_scan')]
        private readonly bool $enabled = false,
        #[Config('koel.streaming.bitrate')]
        private readonly int $bitRate = 128,
        #[Config('koel.streaming.transcode_timeout')]
        private readonly int $transcodeTimeout = 300,
    ) {}

    /**
     * Storage that keeps songs elsewhere scans a temporary local copy, gone right after the scan, so the song
     * is transcoded from it on the spot to save fetching it again; a file that stays on disk is left to a job.
     */
    public function transcodeSongIfNeeded(Song $song, string $localFilePath): void
    {
        if (!$this->enabled || !Streamer::shouldTranscode($song)) {
            return;
        }

        if ($this->storage instanceof MustDeleteTemporaryLocalFileAfterUpload) {
            rescue(fn (): string => TranscodeStrategyFactory::make($song->storage)->getTranscodeLocation(
                $song,
                $this->bitRate,
                $localFilePath,
            ));

            return;
        }

        rescue(fn (): mixed => Dispatcher::dispatch(
            new TranscodeSongJob($song, $this->bitRate, $this->transcodeTimeout),
        ));
    }
}
