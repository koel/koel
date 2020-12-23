<?php

namespace App\Repositories;

use App\Models\Song;
use App\Repositories\Traits\Searchable;

class AlbumRepository extends AbstractRepository
{
    use Searchable;

    /** @return array<int> */
    public function getNonEmptyAlbumIds(): array
    {
        return Song::select('album_id')
            ->groupBy('album_id')
            ->get()
            ->pluck('album_id')
            ->toArray();
    }
}
