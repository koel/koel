<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LastfmService;
use App\Services\TokenManager;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Log\Logger;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use Mockery;

class LastfmTest extends TestCase
{
    public function testGetSessionKey(): void
    {
        /** @var Client $client */
        $client = Mockery::mock(Client::class, [
            'get' => new Response(200, [], file_get_contents(__DIR__.'../../blobs/lastfm/session-key.json')),
        ]);

        $service = new LastfmService($client, app(Cache::class), app(Logger::class));
        self::assertEquals('foo', $service->getSessionKey('bar'));
    }

    public function testSetSessionKey(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $this->postAsUser('api/lastfm/session-key', ['key' => 'foo'], $user)
            ->assertOk();

        self::assertEquals('foo', $user->refresh()->lastfm_session_key);
    }

    public function testConnectToLastfm(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $token = $user->createToken('Koel')->plainTextToken;

        $temporaryToken = Mockery::mock(NewAccessToken::class);
        $temporaryToken->plainTextToken = 'tmp-token';

        $tokenManager = static::mockIocDependency(TokenManager::class);

        $tokenManager->shouldReceive('getUserFromPlainTextToken')
            ->with($token)
            ->andReturn($user);

        $tokenManager->shouldReceive('createToken')
            ->once()
            ->with($user)
            ->andReturn($temporaryToken);

        $this->get('lastfm/connect?api_token='.$token)
            ->assertRedirect(
                'https://www.last.fm/api/auth/?api_key=foo&cb=http%3A%2F%2Flocalhost%2Flastfm%2Fcallback%3Fapi_token%3Dtmp-token'
            );
    }

    public function testCallback(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $token = $user->createToken('Koel')->plainTextToken;

        self::assertNotNull(PersonalAccessToken::findToken($token));

        $lastfm = static::mockIocDependency(LastfmService::class);

        $lastfm->shouldReceive('getSessionKey')
            ->with('lastfm-token')
            ->once()
            ->andReturn('my-session-key');

        $this->get('lastfm/callback?token=lastfm-token&api_token='.urlencode($token))
            ->assertOk();

        self::assertSame('my-session-key', $user->refresh()->lastfm_session_key);
        // make sure the user's api token is deleted
        self::assertNull(PersonalAccessToken::findToken($token));
    }

    public function testRetrieveAndStoreSessionKey(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();

        $lastfm = static::mockIocDependency(LastfmService::class);
        $lastfm->shouldReceive('getSessionKey')
            ->once()
            ->with('foo')
            ->andReturn('bar');

        $tokenManager = static::mockIocDependency(TokenManager::class);
        $tokenManager->shouldReceive('getUserFromPlainTextToken')
            ->once()
            ->with('my-token')
            ->andReturn($user);

        $this->get('lastfm/callback?token=foo&api_token=my-token');

        self::assertEquals('bar', $user->refresh()->lastfm_session_key);
    }

    public function testDisconnectUser(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create(['preferences' => ['lastfm_session_key' => 'bar']]);
        $this->deleteAsUser('api/lastfm/disconnect', [], $user);
        $user->refresh();

        self::assertNull($user->lastfm_session_key);
    }
}
