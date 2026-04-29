<?php

namespace App\Observers;

use App\Facades\Dispatcher;
use App\Jobs\GenerateAlbumThumbnailJob;
use App\Models\Album;
use App\Services\ModelImageCleaner;
use Illuminate\Support\Facades\File;

class AlbumObserver
{
    public function __construct(
        private readonly ModelImageCleaner $cleaner,
    ) {}

    public function saved(Album $album): void
    {
        if ($album->cover && !File::exists(image_storage_path($album->thumbnail))) {
            Dispatcher::dispatch(new GenerateAlbumThumbnailJob($album));
        }
    }

    public function updating(Album $album): void
    {
        if ($album->isDirty('cover')) {
            $this->cleaner->delete($album->getRawOriginal('cover'), hasThumbnail: true);
        }
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
        $this->cleaner->delete($album->cover, hasThumbnail: true);
    }
}
