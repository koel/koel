<?php

namespace App\Observers;

use App\Facades\Dispatcher;
use App\Jobs\GenerateAlbumThumbnailJob;
use App\Models\Album;
use App\Services\Image\ModelImageObserver;
use Illuminate\Support\Facades\File;

class AlbumObserver
{
    private ModelImageObserver $coverObserver;

    public function __construct()
    {
        $this->coverObserver = ModelImageObserver::make(fieldName: 'cover', hasThumbnail: true);
    }

    public function saved(Album $album): void
    {
        if ($album->cover && !File::exists(image_storage_path($album->thumbnail))) {
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
