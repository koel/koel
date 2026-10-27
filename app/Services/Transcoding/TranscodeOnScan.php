<?php

namespace App\Services\Transcoding;

use App\Facades\Dispatcher;
use App\Jobs\CreateSongPreviewJob;
use App\Jobs\QueuedJob;
use App\Jobs\TranscodeSongJob;
use App\Models\Song;
use App\Services\SongStorages\Contracts\MustDeleteTemporaryLocalFileAfterUpload;
use App\Services\SongStorages\SongStorage;
use App\Services\Streamer\Streamer;
use Closure;
use Illuminate\Container\Attributes\Config;

class TranscodeOnScan
{
    public function __construct(
        private readonly SongStorage $storage,
        #[Config('koel.streaming.transcode_on_scan')]
        private readonly bool $enabled = false,
        #[Config('koel.streaming.previews_on_scan')]
        private readonly bool $previewsEnabled = false,
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
        if ($this->enabled && Streamer::shouldTranscode($song)) {
            $this->transcodeNowOrQueue(
                $song,
                fn (TranscodingStrategy $strategy): string => $strategy->getTranscodeLocation(
                    $song,
                    $this->bitRate,
                    $localFilePath,
                ),
                new TranscodeSongJob($song, $this->bitRate, $this->transcodeTimeout),
            );
        }

        if ($this->previewsEnabled && !$song->isEpisode()) {
            $this->transcodeNowOrQueue(
                $song,
                static fn (TranscodingStrategy $strategy): string => $strategy->getPreviewLocation(
                    $song,
                    $localFilePath,
                ),
                new CreateSongPreviewJob($song, $this->transcodeTimeout),
            );
        }
    }

    /** @param Closure(TranscodingStrategy): string $transcodeTemporaryCopy */
    private function transcodeNowOrQueue(Song $song, Closure $transcodeTemporaryCopy, QueuedJob $job): void
    {
        if ($this->storage instanceof MustDeleteTemporaryLocalFileAfterUpload) {
            rescue(static fn (): string => $transcodeTemporaryCopy(TranscodeStrategyFactory::make($song->storage)));

            return;
        }

        rescue(static fn (): mixed => Dispatcher::dispatch($job));
    }
}
