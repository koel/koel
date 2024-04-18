<?php

namespace App\Services\Streamer;

use App\Enums\SongStorageType;
use App\Exceptions\KoelPlusRequiredException;
use App\Models\Song;
use App\Services\Streamer\Adapters\DropboxStreamerAdapter;
use App\Services\Streamer\Adapters\LocalStreamerAdapter;
use App\Services\Streamer\Adapters\S3CompatibleStreamerAdapter;
use App\Services\Streamer\Adapters\StreamerAdapter;
use App\Services\Streamer\Adapters\TranscodingStreamerAdapter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Streamer
{
    private StreamerAdapter $adapter;

    public function __construct(
        private readonly Song $song,
        ?StreamerAdapter $adapter = null,
        private readonly array $config = []
    ) {
        // Turn off error reporting to make sure our stream isn't interfered.
        @error_reporting(0);

        $this->adapter = $adapter ?? $this->resolveAdapter();
    }

    private function resolveAdapter(): StreamerAdapter
    {
        throw_unless($this->song->storage->supported(), KoelPlusRequiredException::class);

        if ($this->shouldTranscode()) {
            return app(TranscodingStreamerAdapter::class);
        }

        return match ($this->song->storage) {
            SongStorageType::LOCAL => app(LocalStreamerAdapter::class),
            SongStorageType::S3, SongStorageType::S3_LAMBDA => app(S3CompatibleStreamerAdapter::class),
            SongStorageType::DROPBOX => app(DropboxStreamerAdapter::class),
        };
    }

    public function stream(): mixed
    {
        return $this->adapter->stream($this->song, $this->config);
    }

    private function shouldTranscode(): bool
    {
        // We only transcode local files. "Remote" transcoding (e.g., from Dropbox) is not supported.
        if ($this->song->storage !== SongStorageType::LOCAL) {
            return false;
        }

        if (Arr::get($this->config, 'transcode', false)) {
            return true;
        }

        return Str::endsWith(File::mimeType($this->song->storage_metadata->getPath()), 'flac')
            && config('koel.streaming.transcode_flac')
            && is_executable(config('koel.streaming.ffmpeg_path'));
    }

    public function getAdapter(): StreamerAdapter
    {
        return $this->adapter;
    }
}
