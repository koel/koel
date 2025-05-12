<?php

namespace App\Services\Streamer\Adapters;

use App\Models\Song;
use App\Services\SongStorages\S3CompatibleStorage;
use App\Values\RequestedStreamingConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

class S3CompatibleStreamerAdapter implements StreamerAdapter
{
    public function __construct(private readonly S3CompatibleStorage $storage)
    {
    }

    public function stream(Song $song, ?RequestedStreamingConfig $config = null): Redirector|RedirectResponse
    {
        return redirect($this->storage->getSongPresignedUrl($song));
    }
}
