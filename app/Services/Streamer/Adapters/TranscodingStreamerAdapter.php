<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Song;
use Illuminate\Support\Arr;

class TranscodingStreamerAdapter implements StreamerAdapter
{
    /**
     * On-the-fly stream the current song while transcoding.
     */
    public function stream(Song $song, array $config = []): void
    {
        $ffmpeg = config('koel.streaming.ffmpeg_path');
        abort_unless(is_executable($ffmpeg), 500, 'Transcoding requires valid ffmpeg settings.');

        $path = $song->storage_metadata->getPath();

        $bitRate = filter_var(Arr::get($config, 'bit_rate'), FILTER_SANITIZE_NUMBER_INT)
            ?: config('koel.streaming.bitrate');

        $startTime = filter_var(Arr::get($config, 'start_time', 0), FILTER_SANITIZE_NUMBER_FLOAT);

        setlocale(LC_CTYPE, 'en_US.UTF-8'); // #1481 special chars might be stripped otherwise

        header('Content-Type: audio/mpeg');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');

        $args = [
            '-i ' . escapeshellarg($path),
            '-map 0:0',
            '-v 0',
            "-ab {$bitRate}k",
            '-f mp3',
            '-',
        ];

        if ($startTime) {
            array_unshift($args, "-ss $startTime");
        }

        passthru("$ffmpeg " . implode(' ', $args));
    }
}
