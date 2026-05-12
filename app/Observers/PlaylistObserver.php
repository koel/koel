<?php

namespace App\Observers;

use App\Models\Playlist;
use App\Services\Image\ModelImageObserver;

class PlaylistObserver
{
    private ModelImageObserver $coverObserver;

    public function __construct()
    {
        $this->coverObserver = ModelImageObserver::make('cover');
    }

    public function updating(Playlist $playlist): void
    {
        $this->coverObserver->onModelUpdating($playlist);
    }

    public function deleted(Playlist $playlist): void
    {
        $this->coverObserver->onModelDeleted($playlist);
    }
}
