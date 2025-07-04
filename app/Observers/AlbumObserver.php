<?php

namespace App\Observers;

use App\Helpers\Ulid;
use App\Models\Album;

class AlbumObserver
{
    public function creating(Album $album): void
    {
        $album->public_id ??= Ulid::generate();
    }

    public function deleted(Album $album): void
    {
        if (!$album->has_cover) {
            return;
        }

        rescue(static fn () => unlink($album->cover_path));
    }

    public function updated(Album $album): void
    {
        if (array_key_exists('name', $album->getChanges())) {
            $album->songs()->update(['album_name' => $album->getChanges()['name']]);
        }
    }
}
