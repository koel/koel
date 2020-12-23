<?php

namespace App\Repositories;

use App\Models\Song;

class ArtistRepository extends AbstractRepository
{
    /** @return array<int> */
    public function getNonEmptyArtistIds(): array
    {
        return Song::select('artist_id')
            ->groupBy('artist_id')
            ->get()
            ->pluck('artist_id')
            ->toArray();
    }
}
