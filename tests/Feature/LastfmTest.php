<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\LastfmService;
use App\Services\UserPreferenceService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Tymon\JWTAuth\JWTAuth;

class LastfmTest extends TestCase
{
    /** @var UserPreferenceService */
    private $userPreferenceService;

    public function setUp()
    {
        parent::setUp();
        $this->userPreferenceService = app(UserPreferenceService::class);
    }

    public function testSetSessionKey(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        $this->postAsUser('api/lastfm/session-key', ['key' => 'foo'], $user)
            ->assertResponseOk();

        $user->refresh();

        self::assertEquals('foo', app(LastfmService::class)->getUserSessionKey($user));
    }

    public function testConnectToLastfm(): void
    {
        $this->mockIocDependency(JWTAuth::class, [
            'parseToken' => null,
            'getToken' => 'foo',
        ]);

        $this->getAsUser('api/lastfm/connect')
            ->assertRedirectedTo('https://www.last.fm/api/auth/?api_key=foo&cb=http%3A%2F%2Flocalhost%2Fapi%2Flastfm%2Fcallback%3Fjwt-token%3Dfoo');
    }

    public function testRetrieveAndStoreSessionKey(): void
    {
        /** @var Client $client */
        $client = Mockery::mock(Client::class, [
            'get' => new Response(200, [], file_get_contents(__DIR__.'../../blobs/lastfm/session-key.xml')),
        ]);

        app()->instance(Client::class, $client);

        /** @var User $user */
        $user = factory(User::class)->create();
        $this->getAsUser('api/lastfm/callback?token=foo', $user);
        $user->refresh();

        self::assertSame('foo', app(LastfmService::class)->getUserSessionKey($user));
    }

    public function testDisconnectUser(): void
    {
        /** @var User $user */
        $user = factory(User::class)->create(['preferences' => ['lastfm_session_key' => 'bar']]);
        $this->deleteAsUser('api/lastfm/disconnect', [], $user);
        $user->refresh();

        $this->assertNull(app(LastfmService::class)->getUserSessionKey($user));
    }
}
