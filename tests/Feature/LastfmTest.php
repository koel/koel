<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LastfmService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Log\Logger;
use Mockery;
use Tymon\JWTAuth\JWTAuth;

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
        $user = factory(User::class)->create();
        $this->postAsUser('api/lastfm/session-key', ['key' => 'foo'], $user)
            ->assertResponseOk();

        $user = User::find($user->id);
        self::assertEquals('foo', $user->lastfm_session_key);
    }

    public function testConnectToLastfm(): void
    {
        static::mockIocDependency(JWTAuth::class, [
            'parseToken' => null,
            'getToken' => 'foo',
        ]);

        $this->getAsUser('api/lastfm/connect')
            ->assertRedirectedTo('https://www.last.fm/api/auth/?api_key=foo&cb=http%3A%2F%2Flocalhost%2Fapi%2Flastfm%2Fcallback%3Fjwt-token%3Dfoo');
    }

    public function testRetrieveAndStoreSessionKey(): void
    {
        $lastfm = static::mockIocDependency(LastfmService::class);
        $lastfm->shouldReceive('getSessionKey')
            ->once()
            ->with('foo')
            ->andReturn('bar');

        /** @var User $user */
        $user = factory(User::class)->create();
        $this->getAsUser('api/lastfm/callback?token=foo', $user);
        $user->refresh();

        $this->assertEquals('bar', $user->lastfm_session_key);
    }

    public function testDisconnectUser(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create(['preferences' => ['lastfm_session_key' => 'bar']]);
        $this->deleteAsUser('api/lastfm/disconnect', [], $user);
        $user->refresh();

        $this->assertNull($user->lastfm_session_key);
    }
}
