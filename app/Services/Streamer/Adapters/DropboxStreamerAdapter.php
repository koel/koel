<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Song;
use App\Services\SongStorages\DropboxStorage;
use App\Values\RequestedStreamingConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

class DropboxStreamerAdapter implements StreamerAdapter
{
    public function __construct(private readonly DropboxStorage $storage)
    {
    }

    public function stream(Song $song, ?RequestedStreamingConfig $config = null): Redirector|RedirectResponse
    {
        $this->storage->assertSupported();

        return redirect($this->storage->getPresignedUrl($song->storage_metadata->getPath()));
    }
}
