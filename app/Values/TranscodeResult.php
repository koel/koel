<?php

namespace App\Values;

use App\Models\Song;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

final class TranscodeResult
{
    public function __construct(public readonly string $path, public readonly string $checksum)
    {
    }

    public static function getForSong(Song $song, int $bitRate, ?string $transcodedPath = null): self
    {
        /** @var self|null $cached */
        $cached = Cache::get("transcoded.{$song->id}.$bitRate");

        return $cached?->valid() ? $cached : self::createForSong($song, $bitRate, $transcodedPath);
    }

    private static function createForSong(Song $song, int $bitRate, ?string $transcodedPath = null): self
    {
        setlocale(LC_CTYPE, 'en_US.UTF-8'); // #1481 special chars might be stripped otherwise

        $dir = sys_get_temp_dir() . '/koel-transcodes';
        $transcodedPath ??= sprintf('%s/%s.%s.mp3', $dir, $song, $bitRate);

        File::ensureDirectoryExists($dir);

        Process::timeout(60)->run([
            config('koel.streaming.ffmpeg_path'),
            '-i',
            $song->storage_metadata->getPath(),
            '-vn',
            '-b:a',
            "{$bitRate}k",
            '-preset',
            'ultrafast',
            '-y', // Overwrite output file if it exists
            $transcodedPath,
        ]);

        $cached = new self($transcodedPath, md5_file($transcodedPath));
        Cache::forever("transcoded.{$song->id}.$bitRate", $cached);

        return $cached;
    }

    private function valid(): bool
    {
        return File::isReadable($this->path) && $this->checksum === md5_file($this->path);
    }
}
