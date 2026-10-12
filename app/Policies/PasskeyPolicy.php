<?php

namespace App\Policies;

use App\Models\Passkey;
use App\Models\User;

class PasskeyPolicy
{
    public function delete(User $user, Passkey $passkey): bool
    {
        return $user->id === $passkey->user_id;
    }
}
