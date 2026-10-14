<?php

namespace App\Observers;

use App\Models\Artist;
use App\Services\Image\ModelImageObserver;
use App\Services\LibrarySummaryService;

class ArtistObserver
{
    private ModelImageObserver $imageObserver;

    public function __construct(
        private readonly LibrarySummaryService $librarySummaryService,
    ) {
        $this->imageObserver = ModelImageObserver::make('image');
    }

    public function updating(Artist $artist): void
    {
        $this->imageObserver->onModelUpdating($artist);
    }

    public function updated(Artist $artist): void
    {
        $changes = $artist->getChanges();

        if (array_key_exists('name', $changes)) {
            // Keep the artist name in sync across songs and albums, but only if it actually changed.
            $artist->songs()->update(['artist_name' => $changes['name']]);
            $artist->albums()->update(['artist_name' => $changes['name']]);
        }

        if ($artist->wasChanged(['name', 'image', 'mbid'])) {
            $this->librarySummaryService->markLibraryChanged();
        }
    }

    public function deleted(Artist $artist): void
    {
        $this->imageObserver->onModelDeleted($artist);
    }
}
