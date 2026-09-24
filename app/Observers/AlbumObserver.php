<?php

namespace App\Observers;

use App\Facades\Dispatcher;
use App\Jobs\GenerateAlbumThumbnailJob;
use App\Models\Album;
use App\Services\Image\ImageStorage;
use App\Services\Image\ModelImageObserver;

class AlbumObserver
{
    private ModelImageObserver $coverObserver;

    public function __construct(
        private readonly ImageStorage $imageStorage,
    ) {
        $this->coverObserver = ModelImageObserver::make(fieldName: 'cover', hasThumbnail: true);
    }

    public function saved(Album $album): void
    {
        if ($album->cover && !$this->imageStorage->exists($album->thumbnail)) {
            Dispatcher::dispatch(new GenerateAlbumThumbnailJob($album));
        }
    }

    public function updating(Album $album): void
    {
        $this->coverObserver->onModelUpdating($album);
    }

    public function updated(Album $album): void
    {
        $changes = $album->getChanges();

        if (array_key_exists('name', $changes)) {
            // Keep the artist name in sync across songs and albums, but only if it actually changed.
            $album->songs()->update(['album_name' => $changes['name']]);
        }
    }

    public function deleted(Album $album): void
    {
        $this->coverObserver->onModelDeleted($album);
    }
}
