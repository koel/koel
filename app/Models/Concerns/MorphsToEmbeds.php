<?php

namespace App\Models\Concerns;

use App\Models\Embed;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait MorphsToEmbeds
{
    public function embeds(): MorphMany
    {
        return $this->morphMany(Embed::class, 'embeddable');
    }
}
