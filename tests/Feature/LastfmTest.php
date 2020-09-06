<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LastfmService;
use App\Services\TokenManager;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Log\Logger;
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

        $this->get('lastfm/connect?api_token='.$token)
            ->assertRedirect(
                'https://www.last.fm/api/auth/?api_key=foo&cb=http%3A%2F%2Flocalhost%2Flastfm%2Fcallback%3Fapi_token%3D'
                .urlencode($token)
            );
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
