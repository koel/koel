<?php

namespace App\Observers;

use App\Models\Playlist;
use App\Services\ImageLifecycle;

class PlaylistObserver
{
    public function __construct(
        private readonly ImageLifecycle $lifecycle,
    ) {}

    public function updating(Playlist $playlist): void
    {
        if ($playlist->isDirty('cover')) {
            $this->lifecycle->onReplaced($playlist->getRawOriginal('cover'));
        }
    }

    public function deleted(Playlist $playlist): void
    {
        $this->lifecycle->onRemoved($playlist->cover);
    }
}
