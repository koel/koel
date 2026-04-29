<?php

namespace App\Observers;

use App\Models\Artist;
use App\Services\ModelImageCleaner;

class ArtistObserver
{
    public function __construct(
        private readonly ModelImageCleaner $cleaner,
    ) {}

    public function updating(Artist $artist): void
    {
        if ($artist->isDirty('image')) {
            $this->cleaner->delete($artist->getRawOriginal('image'));
        }
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
        $this->cleaner->delete($artist->image);
    }
}
