<?php

namespace Tests\Feature;

use App\Services\LastfmService;
use App\Services\TokenManager;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use Mockery;
use Tests\TestCase;

use function Tests\create_user;

class LastfmTest extends TestCase
{
    public function testSetSessionKey(): void
    {
        $user = create_user();
        $this->postAs('api/lastfm/session-key', ['key' => 'foo'], $user)
            ->assertNoContent();

        self::assertSame('foo', $user->refresh()->preferences->lastFmSessionKey);
    }

    public function testConnectToLastfm(): void
    {
        $user = create_user();
        $token = $user->createToken('Koel')->plainTextToken;

        $temporaryToken = Mockery::mock(NewAccessToken::class);
        $temporaryToken->plainTextToken = 'tmp-token';

        $tokenManager = self::mock(TokenManager::class);

        $tokenManager->shouldReceive('getUserFromPlainTextToken')
            ->with($token)
            ->andReturn($user);

        $tokenManager->shouldReceive('createToken')
            ->once()
            ->with($user)
            ->andReturn($temporaryToken);

        $this->get('lastfm/connect?api_token=' . $token)
            ->assertRedirect(
                'https://www.last.fm/api/auth/?api_key=foo&cb=http%3A%2F%2Flocalhost%2Flastfm%2Fcallback%3Fapi_token%3Dtmp-token' // @phpcs-ignore-line
            );
    }

    public function testCallback(): void
    {
        $user = create_user();
        $token = $user->createToken('Koel')->plainTextToken;

        self::assertNotNull(PersonalAccessToken::findToken($token));

        $lastfm = Mockery::mock(LastfmService::class)->makePartial();

        $lastfm->shouldReceive('getSessionKey')
            ->with('lastfm-token')
            ->once()
            ->andReturn('my-session-key');

        app()->instance(LastfmService::class, $lastfm);

        $this->get('lastfm/callback?token=lastfm-token&api_token=' . urlencode($token))
            ->assertOk();

        self::assertSame('my-session-key', $user->refresh()->preferences->lastFmSessionKey);
        // make sure the user's api token is deleted
        self::assertNull(PersonalAccessToken::findToken($token));
    }

    public function testRetrieveAndStoreSessionKey(): void
    {
        $user = create_user();

        $lastfm = Mockery::mock(LastfmService::class)->makePartial();

        $lastfm->shouldReceive('getSessionKey')
            ->once()
            ->with('foo')
            ->andReturn('my-session-key');

        app()->instance(LastfmService::class, $lastfm);

        $tokenManager = self::mock(TokenManager::class);

        $tokenManager->shouldReceive('getUserFromPlainTextToken')
            ->once()
            ->with('my-token')
            ->andReturn($user);

        $this->get('lastfm/callback?token=foo&api_token=my-token');

        self::assertSame('my-session-key', $user->refresh()->preferences->lastFmSessionKey);
    }

    public function testDisconnectUser(): void
    {
        $user = create_user();
        self::assertNotNull($user->preferences->lastFmSessionKey);

        $this->deleteAs('api/lastfm/disconnect', [], $user);

        $user->refresh();
        self::assertNull($user->preferences->lastFmSessionKey);
    }
}
