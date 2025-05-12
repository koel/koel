<?php

namespace App\Services\Streamer;

use App\Enums\SongStorageType;
use App\Exceptions\KoelPlusRequiredException;
use App\Models\Song;
use App\Services\Streamer\Adapters\DropboxStreamerAdapter;
use App\Services\Streamer\Adapters\LocalStreamerAdapter;
use App\Services\Streamer\Adapters\PodcastStreamerAdapter;
use App\Services\Streamer\Adapters\S3CompatibleStreamerAdapter;
use App\Services\Streamer\Adapters\SftpStreamerAdapter;
use App\Services\Streamer\Adapters\StreamerAdapter;
use App\Services\Streamer\Adapters\TranscodingStreamerAdapter;
use App\Values\RequestedStreamingConfig;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Streamer
{
    public function __construct(
        private readonly Song $song,
        private ?StreamerAdapter $adapter = null,
        private readonly ?RequestedStreamingConfig $config = null
    ) {
        $this->adapter ??= $this->resolveAdapter();
    }

    private function resolveAdapter(): StreamerAdapter
    {
        throw_unless($this->song->storage->supported(), KoelPlusRequiredException::class);

        if ($this->song->isEpisode()) {
            return app(PodcastStreamerAdapter::class);
        }

        if ($this->shouldTranscode()) {
            return app(TranscodingStreamerAdapter::class);
        }

        return match ($this->song->storage) {
            SongStorageType::LOCAL => app(LocalStreamerAdapter::class),
            SongStorageType::SFTP => app(SftpStreamerAdapter::class),
            SongStorageType::S3, SongStorageType::S3_LAMBDA => app(S3CompatibleStreamerAdapter::class),
            SongStorageType::DROPBOX => app(DropboxStreamerAdapter::class),
        };
    }

    public function stream(): mixed
    {
        // Turn off error reporting to make sure our stream isn't interfered.
        @error_reporting(0);

        return $this->adapter->stream($this->song, $this->config);
    }

    private function shouldTranscode(): bool
    {
        // We only transcode local files. "Remote" transcoding (e.g., from Dropbox) is not supported.
        if ($this->song->storage !== SongStorageType::LOCAL) {
            return false;
        }

        if ($this->config?->transcode) {
            return true;
        }

        if (!self::hasValidFfmpegInstallation()) {
            Log::warning('No FFmpeg installation available.');

            return false;
        }

        $mimeType = File::mimeType($this->song->storage_metadata->getPath());

        if (Str::endsWith($mimeType, 'flac') && config('koel.streaming.transcode_flac')) {
            return true;
        }

        foreach (config('koel.transcode_required_formats', []) as $format) {
            if (Str::endsWith($mimeType, $format)) {
                return true;
            }
        }

        return false;
    }

    private static function hasValidFfmpegInstallation(): bool
    {
        return app()->runningUnitTests() || is_executable(config('koel.streaming.ffmpeg_path'));
    }

    public function getAdapter(): StreamerAdapter
    {
        return $this->adapter;
    }
}
