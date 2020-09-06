<?php

namespace App\Services;

use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

class TokenManager
{
    public function createToken(User $user, array $abilities = ['*']): NewAccessToken
    {
        return $user->createToken(config('app.name'), $abilities);
    }

    public function destroyTokens(User $user): void
    {
        $user->tokens()->delete();
    }
}
