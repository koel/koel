<?php

namespace App\Services;

use App\Models\User;
use App\Values\CompositionToken;
use Illuminate\Cache\Repository as Cache;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

class TokenManager
{
    public function __construct(private Cache $cache)
    {
    }

    public function createToken(User $user, array $abilities = ['*']): NewAccessToken
    {
        return $user->createToken(config('app.name'), $abilities);
    }

    public function createCompositionToken(User $user): CompositionToken
    {
        $token = CompositionToken::fromAccessTokens(
            api: $this->createToken($user),
            audio: $this->createToken($user, ['audio'])
        );

        $this->cache->rememberForever("app.composition-tokens.$token->apiToken", static fn () => $token->audioToken);

        return $token;
    }

    public function deleteCompositionToken(string $plainTextApiToken): void
    {
        /** @var string $audioToken */
        $audioToken = $this->cache->get("app.composition-tokens.$plainTextApiToken");

        if ($audioToken) {
            self::deleteTokenByPlainTextToken($audioToken);
            $this->cache->forget("app.composition-tokens.$plainTextApiToken");
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
