<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Indicate that a (Model) object collection can be filtered by the current authenticated user.
 */
trait CanFilterByUser
{
    public function scopeByCurrentUser($query): Builder
    {
        return $query->whereUserId(auth()->user()->id);
    }
}
