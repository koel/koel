<?php

namespace App\Repositories;

use App\Models\Album;

class AlbumRepository extends AbstractRepository
{
    public function getModelClass(): string
    {
        return Album::class;
    }
}
