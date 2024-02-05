<?php

namespace App\Services\Streamers;

use App\Services\SongStorage\DropboxStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

class DropboxStreamer extends Streamer
{
    public function __construct(private DropboxStorage $storage)
    {
        parent::__construct();
    }

    public function stream(): Redirector|RedirectResponse
    {
        return redirect($this->storage->getSongPresignedUrl($this->song));
    }
}
