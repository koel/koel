<?php

namespace App\Observers;

use App\Models\Song;

class SongObserver
{
    public function saving(Song $song): void
    {
        if ($song->isDirty('path')) {
            $song->path_hash = simple_hash($song->path);
        }
    }
}
