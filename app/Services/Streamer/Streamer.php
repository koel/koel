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

        if ($this->config?->transcode || self::shouldTranscode($this->song)) {
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
        // Turn off error reporting to make sure our stream isn't interfered with.
        @error_reporting(0);

        return $this->adapter->stream($this->song, $this->config);
    }

    public function getAdapter(): StreamerAdapter
    {
        return $this->adapter;
    }

    /**
     * Determine if the given song should be transcoded based on its format and the server's FFmpeg installation.
     */
    private static function shouldTranscode(Song $song): bool
    {
        if ($song->isEpisode()) {
            return false;
        }

        if (!self::hasValidFfmpegInstallation()) {
            return false;
        }

        if (
            in_array($song->mime_type, ['audio/flac', 'audio/x-flac'], true)
            && config('koel.streaming.transcode_flac')
        ) {
            return true;
        }

        return in_array($song->mime_type, config('koel.streaming.transcode_required_mime_types', []), true);
    }

    private static function hasValidFfmpegInstallation(): bool
    {
        return app()->runningUnitTests() || is_executable(config('koel.streaming.ffmpeg_path'));
    }
}
