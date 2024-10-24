<?php

namespace Tests\Integration\Services;

use App\Services\TokenManager;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class TokenManagerTest extends TestCase
{
    private TokenManager $tokenManager;

    public function setUp(): void
    {
        parent::setUp();

        $this->tokenManager = app(TokenManager::class);
    }

    #[Test]
    public function createTokenWithAllAbilities(): void
    {
        $token = $this->tokenManager->createToken(create_user());

        self::assertTrue($token->accessToken->can('*'));
    }

    #[Test]
    public function createTokenWithSpecificAbilities(): void
    {
        $token = $this->tokenManager->createToken(create_user(), ['audio']);

        self::assertTrue($token->accessToken->can('audio'));
        self::assertFalse($token->accessToken->can('video'));
        self::assertFalse($token->accessToken->can('*'));
    }

    #[Test]
    public function createCompositionToken(): void
    {
        $token = $this->tokenManager->createCompositeToken(create_user());

        self::assertModelExists(PersonalAccessToken::findToken($token->apiToken));

        $audioTokenInstance = PersonalAccessToken::findToken($token->audioToken);
        self::assertModelExists($audioTokenInstance);

        /** @var string $cachedAudioToken */
        $cachedAudioToken = Cache::get("app.composite-tokens.$token->apiToken");
        self::assertTrue($audioTokenInstance->is(PersonalAccessToken::findToken($cachedAudioToken)));
    }

    #[Test]
    public function deleteCompositionToken(): void
    {
        $token = $this->tokenManager->createCompositeToken(create_user());

        $this->tokenManager->deleteCompositionToken($token->apiToken);

        self::assertNull(PersonalAccessToken::findToken($token->apiToken));
        self::assertNull(PersonalAccessToken::findToken($token->audioToken));
        self::assertNull(Cache::get("app.composite-tokens.$token->apiToken"));
    }

    #[Test]
    public function destroyTokens(): void
    {
        $user = create_user();
        $user->createToken('foo');
        $user->createToken('bar');

        self::assertSame(2, $user->tokens()->count());

        $this->tokenManager->destroyTokens($user);

        self::assertSame(0, $user->tokens()->count());
    }

    #[Test]
    public function deleteTokenByPlainTextToken(): void
    {
        $token = $this->tokenManager->createToken(create_user());
        self::assertModelExists($token->accessToken);

        $this->tokenManager->deleteTokenByPlainTextToken($token->plainTextToken);

        self::assertModelMissing($token->accessToken);
    }

    #[Test]
    public function getUserFromPlainTextToken(): void
    {
        $user = create_user();
        $token = $this->tokenManager->createToken($user);

        self::assertTrue($user->is($this->tokenManager->getUserFromPlainTextToken($token->plainTextToken)));
    }

    #[Test]
    public function replaceApiToken(): void
    {
        $oldToken = $this->tokenManager->createToken(create_user());
        $newToken = $this->tokenManager->refreshApiToken($oldToken->plainTextToken);

        self::assertModelMissing($oldToken->accessToken);
        self::assertModelExists($newToken->accessToken);
    }
}
