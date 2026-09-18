<?php

namespace App\Observers;

use App\Models\Playlist;
use App\Services\Image\ImageStorage;
use App\Services\Image\ModelImageObserver;

class PlaylistObserver
{
    private ModelImageObserver $coverObserver;

    public function __construct(ImageStorage $imageStorage)
    {
        $this->coverObserver = ModelImageObserver::make($imageStorage, 'cover');
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
