<?php

namespace App\Observers;

use App\Models\Artist;
use Illuminate\Support\Facades\File;

class ArtistObserver
{
    public function deleted(Artist $artist): void
    {
        if (!$artist->has_image) {
            return;
        }

        File::delete($artist->image_path);
    }

    public function updated(Artist $artist): void
    {
        if (array_key_exists('name', $artist->getChanges())) {
            // Keep the artist name in sync across songs and albums, but only if it actually changed.
            $artist->songs()->update(['artist_name' => $artist->getChanges()['name']]);
            $artist->albums()->update(['artist_name' => $artist->getChanges()['name']]);
        }
    }
}
