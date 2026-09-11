<?php

namespace App\Events;

use App\Models\Artist;

class ArtistMbidResolved extends Event
{
    public function __construct(
        public Artist $artist,
        public string $mbid,
    ) {}
}
