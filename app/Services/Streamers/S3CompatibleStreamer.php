<?php

namespace App\Services\Streamers;

use App\Services\SongStorage\S3CompatibleStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

class S3CompatibleStreamer extends Streamer
{
    public function __construct(private S3CompatibleStorage $storage)
    {
        parent::__construct();
    }

    /**
     * Stream the current song from the Object Storable server.
     * Actually, we just redirect the request to the object's presigned URL.
     */
    public function stream(): Redirector|RedirectResponse
    {
        return redirect($this->storage->getSongPresignedUrl($this->song));
    }
}
