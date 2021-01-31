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

    public function deleteTokenByPlainTextToken(string $plainTextToken): void
    {
        $token = PersonalAccessToken::findToken($plainTextToken);

        if ($token) {
            $token->delete();
        }
    }

    public function getUserFromPlainTextToken(string $plainTextToken): ?User
    {
        $token = PersonalAccessToken::findToken($plainTextToken);

        return $token ? $token->tokenable : null;
    }

    public function refreshToken(User $user): NewAccessToken
    {
        $this->destroyTokens($user);

        return $this->createToken($user);
    }
}
