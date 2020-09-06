<?php

namespace App\Services;

use App\Models\User;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

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

    public function getUserFromPlainTextToken(string $plainTextToken): ?User
    {
        $token = PersonalAccessToken::findToken($plainTextToken);

        return $token ? $token->tokenable : null;
    }
}
