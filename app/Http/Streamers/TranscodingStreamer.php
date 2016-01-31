<?php

namespace App\Http\Streamers;

use App\Models\Song;

class TranscodingStreamer extends BaseStreamer implements StreamerInterface
{
    public function __construct(Song $song)
    {
        parent::__construct($song);
    }

    /**
     * On-the-fly stream the current song while transcoding.
     */
    public function stream()
    {
        if (!is_executable($ffmpeg = env('FFMPEG_PATH', '/usr/local/bin/ffmpeg'))) {
            abort(500, 'Transcoding requires valid ffmpeg settings.');
        }

        $bitRate = filter_var(env('OUTPUT_BIT_RATE', 128), FILTER_SANITIZE_NUMBER_INT);

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

        passthru("$ffmpeg ".implode($args, ' '));

        return;
    }
}
