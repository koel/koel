<?php

namespace App\Repositories;

use App\Models\Song;
use App\Repositories\Traits\Searchable;

class ArtistRepository extends AbstractRepository
{
    use Searchable;

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
