<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Song;
use App\Services\SongStorages\DropboxStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

class DropboxStreamerAdapter implements StreamerAdapter
{
    public function __construct(private DropboxStorage $storage)
    {
    }

    public function stream(Song $song, array $config = []): Redirector|RedirectResponse
    {
        return redirect($this->storage->getSongPresignedUrl($song));
    }
}
