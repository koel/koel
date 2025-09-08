<?php

namespace App\Observers;

use App\Models\Artist;
use Illuminate\Support\Facades\File;

class ArtistObserver
{
    public function updating(Artist $artist): void
    {
        if (!$artist->isDirty('image')) {
            return;
        }

        $oldImage = $artist->getRawOriginal('image');

        rescue_if($oldImage, static fn () => File::delete(image_storage_path($oldImage)));
    }

    public function updated(Artist $artist): void
    {
        $changes = $artist->getChanges();

        if (array_key_exists('name', $changes)) {
            // Keep the artist name in sync across songs and albums, but only if it actually changed.
            $artist->songs()->update(['artist_name' => $changes['name']]);
            $artist->albums()->update(['artist_name' => $changes['name']]);
        }
    }

    public function deleted(Artist $artist): void
    {
        rescue_if($artist->has_image, static fn () => File::delete($artist->image_path));
    }
}
