<?php

namespace Tests\Unit\Http\Integrations\Spotify;

use App\Exceptions\SpotifyIntegrationDisabledException;
use App\Http\Integrations\Spotify\SpotifyClient;
use Illuminate\Cache\Repository as Cache;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use SpotifyWebAPI\Session as SpotifySession;
use SpotifyWebAPI\SpotifyWebAPI;
use Tests\TestCase;

class SpotifyClientTest extends TestCase
{
    private SpotifySession|MockInterface $session;
    private SpotifyWebAPI|MockInterface $wrapped;
    private Cache|MockInterface $cache;

    private SpotifyClient $client;

    public function setUp(): void
    {
        parent::setUp();

        config([
            'koel.services.spotify.client_id' => 'fake-client-id',
            'koel.services.spotify.client_secret' => 'fake-client-secret',
        ]);

        $this->session = Mockery::mock(SpotifySession::class);
        $this->wrapped = Mockery::mock(SpotifyWebAPI::class);
        $this->cache = Mockery::mock(Cache::class);
    }

    #[Test]
    public function accessTokenIsSetUponInitialization(): void
    {
        $this->mockSetAccessToken();

        $this->client = new SpotifyClient($this->wrapped, $this->session, $this->cache);
        self::addToAssertionCount(1);
    }

    #[Test]
    public function accessTokenIsRetrievedFromCacheWhenApplicable(): void
    {
        $this->wrapped->expects('setOptions')->with(['return_assoc' => true]);
        $this->cache->expects('get')->with('spotify.access_token')->andReturn('fake-access-token');
        $this->session->shouldNotReceive('requestCredentialsToken');
        $this->session->shouldNotReceive('getAccessToken');
        $this->cache->shouldNotReceive('put');
        $this->wrapped->expects('setAccessToken')->with('fake-access-token');

        $this->client = new SpotifyClient($this->wrapped, $this->session, $this->cache);
    }

    #[Test]
    public function callForwarding(): void
    {
        $this->mockSetAccessToken();
        $this->wrapped->expects('search')->with('foo', 'track')->andReturn('bar');

        $this->client = new SpotifyClient($this->wrapped, $this->session, $this->cache);

        self::assertSame('bar', $this->client->search('foo', 'track'));
    }

    #[Test]
    public function callForwardingThrowsIfIntegrationIsDisabled(): void
    {
        config([
            'koel.services.spotify.client_id' => null,
            'koel.services.spotify.client_secret' => null,
        ]);

        $this->expectException(SpotifyIntegrationDisabledException::class);
        (new SpotifyClient($this->wrapped, $this->session, $this->cache))->search('foo', 'track');
    }

    private function mockSetAccessToken(): void
    {
        $this->wrapped->expects('setOptions')->with(['return_assoc' => true]);
        $this->cache->expects('get')->with('spotify.access_token')->andReturnNull();
        $this->session->expects('requestCredentialsToken');
        $this->session->expects('getAccessToken')->andReturn('fake-access-token');
        $this->cache->expects('put')->with('spotify.access_token', 'fake-access-token', 3_540);
        $this->wrapped->expects('setAccessToken')->with('fake-access-token');
    }

    protected function tearDown(): void
    {
        config([
            'koel.services.spotify.client_id' => null,
            'koel.services.spotify.client_secret' => null,
        ]);

        parent::tearDown();
    }
}
