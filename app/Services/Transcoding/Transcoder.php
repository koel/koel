<?php

namespace App\Services\Transcoding;

use App\Exceptions\TranscodingFailedException;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class Transcoder
{
    public function __construct(
        #[Config('koel.streaming.transcode_timeout')]
        private readonly int $transcodeTimeout = 0,
        #[Config('koel.streaming.ffmpeg_path')]
        private readonly string $ffmpegPath = '',
        #[Config('koel.streaming.aac_fast')]
        private readonly bool $aacFast = true,
    ) {}

    public function transcode(string $source, string $destination, int $bitRate): void
    {
        setlocale(LC_CTYPE, 'en_US.UTF-8'); // #1481 special chars might be stripped otherwise

        File::ensureDirectoryExists(dirname($destination));

        $process = $this->transcodeTimeout ? Process::timeout($this->transcodeTimeout) : Process::forever();

        $result = $process->run([
            $this->ffmpegPath,
            '-nostdin',
            '-i',
            $source,
            '-vn', // Strip video
            '-c:a',
            'aac',
            '-b:a',
            "{$bitRate}k",
            ...($this->aacFast ? ['-aac_coder', 'fast'] : []),
            '-threads',
            '0',
            '-movflags',
            '+faststart', // Place moov atom at the start for faster streaming
            '-y', // Overwrite if exists
            $destination,
        ]);

        throw_if($result->failed(), new TranscodingFailedException($result->errorOutput()));
    }
}
