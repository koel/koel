<?php

namespace App\Observers;

use App\Helpers\Ulid;
use App\Models\Genre;

class GenreObserver
{
    public function creating(Genre $genre): void
    {
        $genre->public_id ??= Ulid::generate();
    }
}
