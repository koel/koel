<?php

namespace App\Models\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property string $id
 */
interface Rateable
{
    public function ratings(): MorphMany;

    public function getRatingFor(User $user): int;
}
