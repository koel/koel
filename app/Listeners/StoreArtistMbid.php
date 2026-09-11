<?php

namespace App\Listeners;

use App\Events\ArtistMbidResolved;

class StoreArtistMbid
{
    public function handle(ArtistMbidResolved $event): void
    {
        $event->artist->setMbidIfMissing($event->mbid);
    }
}
