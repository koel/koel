<?php

namespace Tests\Unit\Http\Integrations\Spotify;

use App\Exceptions\SpotifyIntegrationDisabledException;
use App\Http\Integrations\Spotify\SpotifyClient;
use Illuminate\Cache\Repository as Cache;
use Mockery;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use SpotifyWebAPI\Session as SpotifySession;
use SpotifyWebAPI\SpotifyWebAPI;
use Tests\TestCase;

class SpotifyClientTest extends TestCase
{
    private SpotifySession|LegacyMockInterface|MockInterface $session;
    private SpotifyWebAPI|LegacyMockInterface|MockInterface $wrapped;
    private Cache|LegacyMockInterface|MockInterface $cache;

    private SpotifyClient $client;

    public function setUp(): void
    {
        parent::setUp();

        config([
            'koel.spotify.client_id' => 'fake-client-id',
            'koel.spotify.client_secret' => 'fake-client-secret',
        ]);

        $this->session = Mockery::mock(SpotifySession::class);
        $this->wrapped = Mockery::mock(SpotifyWebAPI::class);
        $this->cache = Mockery::mock(Cache::class);
    }

    public function testAccessTokenIsSetUponInitialization(): void
    {
        $this->mockSetAccessToken();

        $this->client = new SpotifyClient($this->wrapped, $this->session, $this->cache);
        self::addToAssertionCount(1);
    }

    public function testAccessTokenIsRetrievedFromCacheWhenApplicable(): void
    {
        $this->wrapped->shouldReceive('setOptions')->with(['return_assoc' => true]);
        $this->cache->shouldReceive('get')->with('spotify.access_token')->andReturn('fake-access-token');
        $this->session->shouldNotReceive('requestCredentialsToken');
        $this->session->shouldNotReceive('getAccessToken');
        $this->cache->shouldNotReceive('put');
        $this->wrapped->shouldReceive('setAccessToken')->with('fake-access-token');

        $this->client = new SpotifyClient($this->wrapped, $this->session, $this->cache);
    }

    public function testCallForwarding(): void
    {
        $this->mockSetAccessToken();
        $this->wrapped->shouldReceive('search')->with('foo', 'track')->andReturn('bar');

        $this->client = new SpotifyClient($this->wrapped, $this->session, $this->cache);

        self::assertSame('bar', $this->client->search('foo', 'track'));
    }

    public function testCallForwardingThrowsIfIntegrationIsDisabled(): void
    {
        config([
            'koel.spotify.client_id' => null,
            'koel.spotify.client_secret' => null,
        ]);

        $this->expectException(SpotifyIntegrationDisabledException::class);
        (new SpotifyClient($this->wrapped, $this->session, $this->cache))->search('foo', 'track');
    }

    private function mockSetAccessToken(): void
    {
        $this->wrapped->shouldReceive('setOptions')->with(['return_assoc' => true]);
        $this->cache->shouldReceive('get')->with('spotify.access_token')->andReturnNull();
        $this->session->shouldReceive('requestCredentialsToken');
        $this->session->shouldReceive('getAccessToken')->andReturn('fake-access-token');
        $this->cache->shouldReceive('put')->with('spotify.access_token', 'fake-access-token', 3_540);
        $this->wrapped->shouldReceive('setAccessToken')->with('fake-access-token');
    }

    protected function tearDown(): void
    {
        config([
            'koel.spotify.client_id' => null,
            'koel.spotify.client_secret' => null,
        ]);

        parent::tearDown();
    }
}
