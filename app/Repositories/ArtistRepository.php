<?php

namespace App\Repositories;

use App\Models\Artist;

class ArtistRepository extends AbstractRepository
{
    public function getModelClass(): string
    {
        return Artist::class;
    }
}
