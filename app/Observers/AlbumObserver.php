<?php

namespace App\Observers;

use App\Models\Album;

class AlbumObserver
{
    public function deleted(Album $album): void
    {
        if (!$album->has_cover) {
            return;
        }

        attempt(static fn () => unlink($album->cover_path));
    }
}
