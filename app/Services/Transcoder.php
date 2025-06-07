<?php

namespace App\Services;

use App\Enums\SongStorageType;
use App\Models\Song;
use App\Services\SongStorages\CloudStorage;
use App\Services\SongStorages\SftpStorage;
use App\Values\TranscodeResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Webmozart\Assert\Assert;

class Transcoder
{
    public function getTranscodedPath(Song $song, int $bitRate = 128): string
    {
        Assert::false($song->isEpisode());

        /** @var ?TranscodeResult $cachedResult */
        $cachedResult = Cache::get("transcoded.{$song->id}.$bitRate");

        $result = $cachedResult?->valid() ? $cachedResult : $this->transcode($song, $bitRate);

        return $result->path;
    }

    private function transcode(Song $song, int $bitRate = 128): TranscodeResult
    {
        // Get a path to transcode from.
        // For a local song, we use the path from the storage metadata (which actually is the "path" property).
        // For (S)FTP songs, we download the file to a temporary location to the local filesystem.
        // For cloud songs, we get a presigned URL, knowing FFmpeg can handle it directly.
        $path = match ($song->storage) {
            SongStorageType::LOCAL => $song->storage_metadata->getPath(),
            SongStorageType::SFTP => app(SftpStorage::class)->copyToLocal($song),
            default => CloudStorage::resolve($song)?->getSongPresignedUrl($song),
        };

        throw_unless($path);

        setlocale(LC_CTYPE, 'en_US.UTF-8'); // #1481 special chars might be stripped otherwise

        $dir = sys_get_temp_dir() . '/koel-transcodes';
        $transcodedPath = sprintf('%s/%s.%s.mp3', $dir, $song, $bitRate);

        File::ensureDirectoryExists($dir);

        Process::timeout(60)->run([
            config('koel.streaming.ffmpeg_path'),
            '-i',
            $path,
            '-vn',
            '-b:a',
            "{$bitRate}k",
            '-preset',
            'ultrafast',
            '-y', // Overwrite the output file if it exists
            $transcodedPath,
        ]);

        $transcodeResult = new TranscodeResult($transcodedPath, File::hash($transcodedPath));
        Cache::forever("transcoded.{$song->id}.$bitRate", $transcodeResult);

        // @todo: For S3 songs, we should upload the transcoded file back to the storage and keep track of it somehow.

        return $transcodeResult;
    }

    public static function shouldTranscode(Song $song): bool
    {
        if ($song->isEpisode()) {
            return false;
        }

        if (!self::hasValidFfmpegInstallation()) {
            return false;
        }

        $extension = Str::lower(File::extension($song->storage_metadata->getPath()));

        if ($extension === 'flac' && config('koel.streaming.transcode_flac')) {
            return true;
        }

        return in_array($extension, config('koel.transcode_required_formats', []), true);
    }

    private static function hasValidFfmpegInstallation(): bool
    {
        return app()->runningUnitTests() || is_executable(config('koel.streaming.ffmpeg_path'));
    }
}
