<?php

namespace App\Observers;

use App\Helpers\Uuid;
use App\Models\User;

class UserObserver
{
    public function creating(User $user): void
    {
        $user->public_id ??= Uuid::generate();
    }
}
