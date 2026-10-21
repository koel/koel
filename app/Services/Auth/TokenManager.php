<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Values\CompositeToken;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use SensitiveParameter;

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
            audio: $this->createToken($user, ['audio']),
        );

        Cache::forever("app.composite-tokens.$token->apiToken", $token->audioToken);

        return $token;
    }

    public function deleteCompositionToken(#[SensitiveParameter] string $plainTextApiToken): void
    {
        /** @var string $audioToken */
        $audioToken = Cache::get("app.composite-tokens.$plainTextApiToken");

        if ($audioToken) {
            $this->deleteTokenByPlainTextToken($audioToken);
            Cache::forget("app.composite-tokens.$plainTextApiToken");
        }

        $this->deleteTokenByPlainTextToken($plainTextApiToken);
    }

    public function destroyTokens(User $user): void
    {
        $user->tokens()->delete();
    }

    /**
     * Delete every token of the user except the API token in use and the audio token created with it.
     */
    public function destroyOtherSessions(User $user, #[SensitiveParameter] string $currentPlainTextApiToken): void
    {
        /** @var ?string $currentAudioToken */
        $currentAudioToken = Cache::get("app.composite-tokens.$currentPlainTextApiToken");

        $currentSessionTokenIds = collect([$currentPlainTextApiToken, $currentAudioToken])
            ->filter()
            ->map(PersonalAccessToken::findToken(...))
            ->filter()
            ->map
            ->getKey()
            ->all();

        $user->tokens()->whereKeyNot($currentSessionTokenIds)->delete();
    }

    public function deleteTokenByPlainTextToken(#[SensitiveParameter] string $plainTextToken): void
    {
        PersonalAccessToken::findToken($plainTextToken)?->delete();
    }

    public function getUserFromPlainTextToken(#[SensitiveParameter] string $plainTextToken): ?User
    {
        return PersonalAccessToken::findToken($plainTextToken)?->tokenable;
    }

    public function refreshApiToken(#[SensitiveParameter] string $currentPlainTextToken): NewAccessToken
    {
        $newToken = $this->createToken($this->getUserFromPlainTextToken($currentPlainTextToken));

        /** @var ?string $audioToken */
        $audioToken = Cache::pull("app.composite-tokens.$currentPlainTextToken");

        if ($audioToken) {
            Cache::forever("app.composite-tokens.$newToken->plainTextToken", $audioToken);
        }

        $this->deleteTokenByPlainTextToken($currentPlainTextToken);

        return $newToken;
    }
}
