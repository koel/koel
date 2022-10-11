<?php

namespace App\Builders;

use App\Models\Artist;
use Illuminate\Database\Eloquent\Builder;

class ArtistBuilder extends Builder
{
    public function isStandard(): static
    {
        return $this->whereNotIn('artists.id', [Artist::UNKNOWN_ID, Artist::VARIOUS_ID]);
    }
}
