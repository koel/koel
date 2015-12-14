<?php

namespace App\Traits;

/**
 * Indicate that a (Model) object collection can be filtered by the current authenticated user.
 */
trait CanFilterByUser
{
    public function scopeByCurrentUser($query)
    {
        return $query->whereUserId(auth()->user()->id);
    }
}
