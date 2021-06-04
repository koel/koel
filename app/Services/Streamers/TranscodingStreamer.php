<?php

namespace App\Services\Streamers;

class TranscodingStreamer extends Streamer implements TranscodingStreamerInterface
{
    /**
     * Bit rate the stream should be transcoded at.
     */
    private ?int $bitRate = null;

    /**
     * Time point to start transcoding from.
     */
    private ?float $startTime = null;

    /**
     * On-the-fly stream the current song while transcoding.
     */
    public function stream(): void
    {
        $ffmpeg = config('koel.streaming.ffmpeg_path');
        abort_unless(is_executable($ffmpeg), 500, 'Transcoding requires valid ffmpeg settings.');

        $bitRate = filter_var($this->bitRate, FILTER_SANITIZE_NUMBER_INT);

        header('Content-Type: audio/mpeg');
        header('Content-Disposition: attachment; filename="' . basename($this->song->path) . '"');

        $args = [
            '-i ' . escapeshellarg($this->song->path),
            '-map 0:0',
            '-v 0',
            "-ab {$bitRate}k",
            '-f mp3',
            '-',
        ];

        if ($this->startTime) {
            array_unshift($args, "-ss {$this->startTime}");
        }

        passthru("$ffmpeg " . implode(' ', $args));
    }

    public function setBitRate(int $bitRate): void
    {
        $this->bitRate = $bitRate;
    }

    public function setStartTime(float $startTime): void
    {
        $this->startTime = $startTime;
    }
}
