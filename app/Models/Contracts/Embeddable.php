<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property-read string $id
 */
interface Embeddable
{
    public function embeds(): MorphMany;
}
