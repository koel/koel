<?php

namespace Tests\Feature;

use App\Services\LastfmService;
use App\Services\TokenManager;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class LastfmTest extends TestCase
{
    #[Test]
    public function setSessionKey(): void
    {
        $user = create_user();
        $this->postAs('api/lastfm/session-key', ['key' => 'foo'], $user)
            ->assertNoContent();

        self::assertSame('foo', $user->refresh()->preferences->lastFmSessionKey);
    }

    #[Test]
    public function connectToLastfm(): void
    {
        $user = create_user();
        $token = $user->createToken('Koel')->plainTextToken;

        /** @var NewAccessToken|MockInterface $temporaryToken */
        $temporaryToken = Mockery::mock(NewAccessToken::class);
        $temporaryToken->plainTextToken = 'tmp-token';

        /** @var TokenManager|MockInterface $tokenManager */
        $tokenManager = $this->mock(TokenManager::class);

        $tokenManager->expects('getUserFromPlainTextToken')
            ->with($token)
            ->andReturn($user);

        $tokenManager->expects('createToken')
            ->with($user)
            ->andReturn($temporaryToken);

        $this->get('lastfm/connect?api_token=' . $token)
            ->assertRedirect(
                'https://www.last.fm/api/auth/?api_key=foo&cb=http%3A%2F%2Flocalhost%2Flastfm%2Fcallback%3Fapi_token%3Dtmp-token' // @phpcs-ignore-line
            );
    }

    #[Test]
    public function testCallback(): void
    {
        $user = create_user();
        $token = $user->createToken('Koel')->plainTextToken;

        self::assertNotNull(PersonalAccessToken::findToken($token));

        /** @var LastfmService|MockInterface $lastfm */
        $lastfm = Mockery::mock(LastfmService::class)->makePartial();

        $lastfm->expects('getSessionKey')
            ->with('lastfm-token')
            ->andReturn('my-session-key');

        app()->instance(LastfmService::class, $lastfm);

        $this->get('lastfm/callback?token=lastfm-token&api_token=' . urlencode($token))
            ->assertOk();

        self::assertSame('my-session-key', $user->refresh()->preferences->lastFmSessionKey);
        // make sure the user's api token is deleted
        self::assertNull(PersonalAccessToken::findToken($token));
    }

    #[Test]
    public function retrieveAndStoreSessionKey(): void
    {
        $user = create_user();

        /** @var LastfmService|MockInterface $lastfm */
        $lastfm = Mockery::mock(LastfmService::class)->makePartial();

        $lastfm->expects('getSessionKey')
            ->with('foo')
            ->andReturn('my-session-key');

        app()->instance(LastfmService::class, $lastfm);

        $tokenManager = $this->mock(TokenManager::class);

        $tokenManager->expects('getUserFromPlainTextToken')
            ->with('my-token')
            ->andReturn($user);

        $tokenManager->expects('deleteTokenByPlainTextToken');

        $this->get('lastfm/callback?token=foo&api_token=my-token');

        self::assertSame('my-session-key', $user->refresh()->preferences->lastFmSessionKey);
    }

    #[Test]
    public function disconnectUser(): void
    {
        $user = create_user();
        self::assertNotNull($user->preferences->lastFmSessionKey);

        $this->deleteAs('api/lastfm/disconnect', [], $user);

        $user->refresh();
        self::assertNull($user->preferences->lastFmSessionKey);
    }
}
