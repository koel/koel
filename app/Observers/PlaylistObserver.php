<?php

namespace App\Observers;

use App\Models\Playlist;
use Illuminate\Support\Facades\File;

class PlaylistObserver
{
    public function updating(Playlist $playlist): void
    {
        if (!$playlist->isDirty('cover')) {
            return;
        }

        $oldCover = $playlist->getRawOriginal('cover');

        // If the cover is being updated, delete the old cover
        rescue_if($oldCover, static fn () => File::delete(image_storage_path($oldCover)));
    }

    public function deleted(Playlist $playlist): void
    {
        rescue_if($playlist->cover_path, static fn () => File::delete($playlist->cover_path));
    }
}
