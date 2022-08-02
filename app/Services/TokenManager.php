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
        PersonalAccessToken::findToken($plainTextToken)?->delete();
    }

    public function getUserFromPlainTextToken(string $plainTextToken): ?User
    {
        return PersonalAccessToken::findToken($plainTextToken)?->tokenable;
    }

    public function refreshToken(User $user): NewAccessToken
    {
        $this->destroyTokens($user);

        return $this->createToken($user);
    }
}
