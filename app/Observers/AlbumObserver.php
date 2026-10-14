<?php

namespace App\Observers;

use App\Facades\Dispatcher;
use App\Jobs\GenerateAlbumSmallCoverJob;
use App\Jobs\GenerateAlbumThumbnailJob;
use App\Models\Album;
use App\Services\Image\ImageStorage;
use App\Services\Image\ModelImageObserver;

class AlbumObserver
{
    private ModelImageObserver $coverObserver;
    private ModelImageObserver $smallCoverObserver;

    public function __construct(
        private readonly ImageStorage $imageStorage,
    ) {
        $this->coverObserver = ModelImageObserver::make(fieldName: 'cover', hasThumbnail: true);
        $this->smallCoverObserver = ModelImageObserver::make(fieldName: 'small_cover');
    }

    public function saved(Album $album): void
    {
        if ($album->cover && !$this->imageStorage->exists($album->thumbnail)) {
            Dispatcher::dispatch(new GenerateAlbumThumbnailJob($album));
        }

        if ($album->cover && !$album->small_cover) {
            Dispatcher::dispatch(new GenerateAlbumSmallCoverJob($album));
        }
    }

    public function updating(Album $album): void
    {
        $this->coverObserver->onModelUpdating($album);

        if ($album->isDirty('cover')) {
            $album->small_cover = null;
        }

        $this->smallCoverObserver->onModelUpdating($album);
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
        $this->smallCoverObserver->onModelDeleted($album);
    }
}
