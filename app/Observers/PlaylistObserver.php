<?php

namespace App\Observers;

use App\Models\Playlist;
use App\Services\ModelImageCleaner;

class PlaylistObserver
{
    public function __construct(
        private readonly ModelImageCleaner $cleaner,
    ) {}

    public function updating(Playlist $playlist): void
    {
        if ($playlist->isDirty('cover')) {
            $this->cleaner->delete($playlist->getRawOriginal('cover'));
        }
    }

    public function deleted(Playlist $playlist): void
    {
        $this->cleaner->delete($playlist->cover);
    }
}
