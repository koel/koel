<?php

namespace App\Services\Streamer;

use App\Enums\SongStorageType;
use App\Exceptions\KoelPlusRequiredException;
use App\Models\Song;
use App\Services\Streamer\Adapters\DropboxStreamerAdapter;
use App\Services\Streamer\Adapters\LocalStreamerAdapter;
use App\Services\Streamer\Adapters\PodcastStreamerAdapter;
use App\Services\Streamer\Adapters\ProgressiveTranscodingStreamerAdapter;
use App\Services\Streamer\Adapters\S3CompatibleStreamerAdapter;
use App\Services\Streamer\Adapters\SftpStreamerAdapter;
use App\Services\Streamer\Adapters\StreamerAdapter;
use App\Services\Streamer\Adapters\TranscodingStreamerAdapter;
use App\Services\Streamer\Adapters\WebDAVStreamerAdapter;
use App\Services\Transcoding\TranscodingPolicy;
use App\Values\RequestedStreamingConfig;

class Streamer
{
    public function __construct(
        private readonly Song $song,
        private ?StreamerAdapter $adapter = null,
        private readonly ?RequestedStreamingConfig $config = null,
        private readonly ?TranscodingPolicy $transcodingPolicy = null,
    ) {
        $this->adapter ??= $this->resolveAdapter();
    }

    private function resolveAdapter(): StreamerAdapter
    {
        throw_unless($this->song->storage->supported(), KoelPlusRequiredException::class);

        if ($this->song->isEpisode()) {
            return app(PodcastStreamerAdapter::class);
        }

        $transcodingPolicy = $this->transcodingPolicy ?? app(TranscodingPolicy::class);

        if ($transcodingPolicy->shouldStreamProgressively($this->song, $this->config)) {
            return app(ProgressiveTranscodingStreamerAdapter::class);
        }

        if ($this->config?->transcode || $transcodingPolicy->requiresTranscoding($this->song)) {
            return app(TranscodingStreamerAdapter::class);
        }

        return match ($this->song->storage) {
            SongStorageType::LOCAL => app(LocalStreamerAdapter::class),
            SongStorageType::SFTP => app(SftpStreamerAdapter::class),
            SongStorageType::S3, SongStorageType::S3_LAMBDA => app(S3CompatibleStreamerAdapter::class),
            SongStorageType::DROPBOX => app(DropboxStreamerAdapter::class),
            SongStorageType::WEBDAV => app(WebDAVStreamerAdapter::class),
        };
    }

    public function stream(): mixed
    {
        // Turn off error reporting to make sure our stream isn't interfered with.
        // @mago-ignore lint:no-error-control-operator
        @error_reporting(0);

        return $this->adapter->stream($this->song, $this->config);
    }

    public function getAdapter(): StreamerAdapter
    {
        return $this->adapter;
    }
}
