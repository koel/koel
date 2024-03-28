<?php

namespace App\Services\Streamer;

use App\Exceptions\KoelPlusRequiredException;
use App\Exceptions\UnsupportedSongStorageTypeException;
use App\Models\Song;
use App\Services\Streamer\Adapters\DropboxStreamerAdapter;
use App\Services\Streamer\Adapters\LocalStreamerAdapter;
use App\Services\Streamer\Adapters\S3CompatibleStreamerAdapter;
use App\Services\Streamer\Adapters\StreamerAdapter;
use App\Services\Streamer\Adapters\TranscodingStreamerAdapter;
use App\Values\SongStorageTypes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Streamer
{
    private StreamerAdapter $adapter;

    public function __construct(private Song $song, ?StreamerAdapter $adapter = null, private array $config = [])
    {
        // Turn off error reporting to make sure our stream isn't interfered.
        @error_reporting(0);

        $this->adapter = $adapter ?? $this->resolveAdapter();
    }

    private function resolveAdapter(): StreamerAdapter
    {
        throw_unless(SongStorageTypes::supported($this->song->storage), KoelPlusRequiredException::class);

        if ($this->shouldTranscode()) {
            return app(TranscodingStreamerAdapter::class);
        }

        return match ($this->song->storage) {
            SongStorageTypes::LOCAL, '' => app(LocalStreamerAdapter::class),
            SongStorageTypes::S3, SongStorageTypes::S3_LAMBDA => app(S3CompatibleStreamerAdapter::class),
            SongStorageTypes::DROPBOX => app(DropboxStreamerAdapter::class),
            default => throw UnsupportedSongStorageTypeException::create($this->song->storage),
        };
    }

    public function stream(): mixed
    {
        return $this->adapter->stream($this->song, $this->config);
    }

    private function shouldTranscode(): bool
    {
        // We only transcode local files. "Remote" transcoding (e.g., from Dropbox) is not supported.
        if ($this->song->storage !== SongStorageTypes::LOCAL) {
            return false;
        }

        if (Arr::get($this->config, 'transcode', false)) {
            return true;
        }

        return $this->song->storage === SongStorageTypes::LOCAL
            && Str::endsWith(File::mimeType($this->song->storage_metadata->getPath()), 'flac')
            && config('koel.streaming.transcode_flac')
            && is_executable(config('koel.streaming.ffmpeg_path'));
    }

    public function getAdapter(): StreamerAdapter
    {
        return $this->adapter;
    }
}
