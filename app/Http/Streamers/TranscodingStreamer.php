<?php

namespace App\Http\Streamers;

use App\Models\Song;

class TranscodingStreamer extends Streamer implements StreamerInterface
{
    /**
     * Bitrate the stream should be transcoded at.
     *
     * @var int
     */
    private $bitrate;

    /**
     * Time point to start transcoding from.
     *
     * @var int
     */
    private $startTime;

    public function __construct(Song $song, $bitrate, $startTime = 0)
    {
        parent::__construct($song);
        $this->bitrate = $bitrate;
        $this->startTime = $startTime;
    }

    /**
     * On-the-fly stream the current song while transcoding.
     */
    public function stream()
    {
        $ffmpeg = env('FFMPEG_PATH', '/usr/local/bin/ffmpeg');
        abort_unless(is_executable($ffmpeg), 500, 'Transcoding requires valid ffmpeg settings.');

        $bitRate = filter_var($this->bitrate, FILTER_SANITIZE_NUMBER_INT);

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
