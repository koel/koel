<?php

namespace Tests\Integration\Services;

use App\Models\User;
use App\Services\TokenManager;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class TokenManagerTest extends TestCase
{
    private TokenManager $tokenManager;

    public function setUp(): void
    {
        parent::setUp();

        $this->tokenManager = app(TokenManager::class);
    }

    public function testCreateTokenWithAllAbilities(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $token = $this->tokenManager->createToken($user);

        self::assertTrue($token->accessToken->can('*'));
    }

    public function testCreateTokenWithSpecificAbilities(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $token = $this->tokenManager->createToken($user, ['audio']);

        self::assertTrue($token->accessToken->can('audio'));
        self::assertFalse($token->accessToken->can('video'));
        self::assertFalse($token->accessToken->can('*'));
    }

    public function testCreateCompositionToken(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $token = $this->tokenManager->createCompositeToken($user);

        self::assertModelExists(PersonalAccessToken::findToken($token->apiToken));

        $audioTokenInstance = PersonalAccessToken::findToken($token->audioToken);
        self::assertModelExists($audioTokenInstance);

        /** @var string $cachedAudioToken */
        $cachedAudioToken = Cache::get("app.composite-tokens.$token->apiToken");
        self::assertTrue($audioTokenInstance->is(PersonalAccessToken::findToken($cachedAudioToken)));
    }

    public function testDeleteCompositionToken(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $token = $this->tokenManager->createCompositeToken($user);

        $this->tokenManager->deleteCompositionToken($token->apiToken);

        self::assertNull(PersonalAccessToken::findToken($token->apiToken));
        self::assertNull(PersonalAccessToken::findToken($token->audioToken));
        self::assertNull(Cache::get("app.composite-tokens.$token->apiToken"));
    }

    public function testDestroyTokens(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->createToken('foo');
        $user->createToken('bar');

        self::assertSame(2, $user->tokens()->count());

        $this->tokenManager->destroyTokens($user);

        self::assertSame(0, $user->tokens()->count());
    }

    public function testDeleteTokenByPlainTextToken(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $token = $this->tokenManager->createToken($user);
        self::assertModelExists($token->accessToken);

        $this->tokenManager->deleteTokenByPlainTextToken($token->plainTextToken);

        self::assertModelMissing($token->accessToken);
    }

    public function testGetUserFromPlainTextToken(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $token = $this->tokenManager->createToken($user);

        self::assertTrue($user->is($this->tokenManager->getUserFromPlainTextToken($token->plainTextToken)));
    }

    public function testReplaceApiToken(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $oldToken = $this->tokenManager->createToken($user);
        $newToken = $this->tokenManager->refreshApiToken($oldToken->plainTextToken);

        self::assertModelMissing($oldToken->accessToken);
        self::assertModelExists($newToken->accessToken);
    }
}
