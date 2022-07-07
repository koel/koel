<?php

namespace App\Observers;

use App\Models\Song;
use App\Services\Helper;

class SongObserver
{
    public function creating(Song $song): void
    {
        $song->id = Helper::getFileHash($song->path);
    }
}
