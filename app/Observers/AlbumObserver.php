<?php

namespace App\Observers;

use App\Models\Album;
use Illuminate\Support\Facades\File;

class AlbumObserver
{
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
        rescue_if(
            $album->has_cover,
            static fn () => File::delete([$album->cover_path, $album->thumbnail_path]),
        );
    }
}
