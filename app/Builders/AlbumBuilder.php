<?php

namespace App\Builders;

use App\Models\Album;
use Illuminate\Database\Eloquent\Builder;

class AlbumBuilder extends Builder
{
    public function isStandard(): static
    {
        return $this->whereNot('albums.id', Album::UNKNOWN_ID);
    }
}
