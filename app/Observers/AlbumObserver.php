<?php

namespace App\Observers;

use App\Facades\Dispatcher;
use App\Jobs\GenerateAlbumThumbnailJob;
use App\Models\Album;
use Illuminate\Support\Facades\File;

class AlbumObserver
{
    public function saved(Album $album): void
    {
        if ($album->cover && !File::exists(image_storage_path($album->thumbnail))) {
            Dispatcher::dispatch(new GenerateAlbumThumbnailJob($album));
        }
    }

    public function updating(Album $album): void
    {
        if (!$album->isDirty('cover')) {
            return;
        }

        $oldCover = $album->getRawOriginal('cover');

        // If the cover is being updated, delete the old cover and thumbnail files
        rescue_if(
            $oldCover,
            static function () use ($oldCover): void {
                $oldCoverPath = image_storage_path($oldCover);
                $parts = pathinfo($oldCoverPath);

                $oldThumbnail = sprintf('%s_thumb.%s', $parts['filename'], $parts['extension']);
                File::delete([$oldCoverPath, image_storage_path($oldThumbnail)]);
            },
        );
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
        $coverPath = image_storage_path($album->cover);
        $thumbnailPath = image_storage_path($album->thumbnail);

        rescue_if($coverPath || $thumbnailPath, static fn () => File::delete([$coverPath, $thumbnailPath]));
    }
}
