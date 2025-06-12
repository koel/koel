<?php

namespace App\Services\Transcoding;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class Transcoder
{
    public function transcode(string $source, string $destination, int $bitRate): void
    {
        setlocale(LC_CTYPE, 'en_US.UTF-8'); // #1481 special chars might be stripped otherwise

        File::ensureDirectoryExists(dirname($destination));

        Process::timeout(60)->run([
            config('koel.streaming.ffmpeg_path'),
            '-i', $source,
            '-vn',                     // Strip video
            '-c:a', 'aac',             // Use native AAC encoder for its efficiency
            '-b:a', "{$bitRate}k",     // Set target bitrate (e.g., 128k, 192k)
            '-y',                      // Overwrite if exists
            $destination,
        ]);
    }
}
