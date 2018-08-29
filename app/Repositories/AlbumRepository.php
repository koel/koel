<?php

namespace App\Repositories;

use App\Models\Album;
use App\Models\Song;

class AlbumRepository extends AbstractRepository
{
    public function getModelClass(): string
    {
        return Album::class;
    }

    public function getNonEmptyAlbumIds(): array
    {
        $ids = Song::select('album_id')
            ->groupBy('album_id')
            ->get()
            ->pluck('album_id')
            ->toArray();

        return $ids;
    }
}
