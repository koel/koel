<?php

namespace App\Builders\Concerns;

use App\Models\User;

trait CanScopeByUser
{
    protected ?User $user = null;

    public function setScopedUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
