<?php

namespace App\Services;

use App\Models\User;
use App\Values\CompositeToken;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

class TokenManager
{
    public function createToken(User $user, array $abilities = ['*']): NewAccessToken
    {
        return $user->createToken(config('app.name'), $abilities);
    }

    public function createCompositeToken(User $user): CompositeToken
    {
        $token = CompositeToken::fromAccessTokens(
            api: $this->createToken($user),
            audio: $this->createToken($user, ['audio'])
        );

        Cache::forever("app.composite-tokens.$token->apiToken", $token->audioToken);

        return $token;
    }

    public function deleteCompositionToken(string $plainTextApiToken): void
    {
        /** @var string $audioToken */
        $audioToken = Cache::get("app.composite-tokens.$plainTextApiToken");

        if ($audioToken) {
            self::deleteTokenByPlainTextToken($audioToken);
            Cache::forget("app.composite-tokens.$plainTextApiToken");
        }

        self::deleteTokenByPlainTextToken($plainTextApiToken);
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

    public function refreshApiToken(string $currentPlainTextToken): NewAccessToken
    {
        $newToken = $this->createToken($this->getUserFromPlainTextToken($currentPlainTextToken));
        $this->deleteTokenByPlainTextToken($currentPlainTextToken);

        return $newToken;
    }
}
