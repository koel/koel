<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property string $id
 * @property bool $favorite
 */
interface Favoriteable
{
    public function favorites(): MorphMany;
}
