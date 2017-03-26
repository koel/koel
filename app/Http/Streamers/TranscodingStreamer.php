<?php

namespace App\Http\Streamers;

use App\Models\Song;

class TranscodingStreamer extends Streamer implements StreamerInterface
{
    /**
     * Bit rate the stream should be transcoded at.
     *
     * @var int
     */
    private $bitRate;

    /**
     * Time point to start transcoding from.
     *
     * @var int
     */
    private $startTime;

    public function __construct(Song $song, $bitRate, $startTime = 0)
    {
        parent::__construct($song);
        $this->bitRate = $bitRate;
        $this->startTime = $startTime;
    }

    /**
     * On-the-fly stream the current song while transcoding.
     */
    public function stream()
    {
        $ffmpeg = config('koel.streaming.ffmpeg_path');
        abort_unless(is_executable($ffmpeg), 500, 'Transcoding requires valid ffmpeg settings.');

        $bitRate = filter_var($this->bitRate, FILTER_SANITIZE_NUMBER_INT);

        // Since we can't really know the content length of a file while it's still being transcoded,
        // "calculating" it (like below) will be much likely to result in net::ERR_CONTENT_LENGTH_MISMATCH errors.
        // Better comment these for now.
        //
        // header('Accept-Ranges: bytes');
        // $bytes = round(($this->song->length * $bitRate * 1024) / 8);
        // header("Content-Length: $bytes");

        header('Content-Type: audio/mpeg');
        header('Content-Disposition: attachment; filename="'.basename($this->song->path).'"');

        $args = [
            '-i '.escapeshellarg($this->song->path),
            '-map 0:0',
            '-v 0',
            "-ab {$bitRate}k",
            '-f mp3',
            '-',
        ];

        if ($this->startTime) {
            array_unshift($args, "-ss {$this->startTime}");
        }

        passthru("$ffmpeg ".implode($args, ' '));
    }
}
