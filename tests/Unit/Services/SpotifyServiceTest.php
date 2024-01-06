<?php

namespace Tests\Unit\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Services\ApiClients\SpotifyClient;
use App\Services\SpotifyService;
use Illuminate\Support\Facades\File;
use Mockery;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Tests\TestCase;

class SpotifyServiceTest extends TestCase
{
    private SpotifyService $service;
    private SpotifyClient|MockInterface|LegacyMockInterface $client;

    public function setUp(): void
    {
        parent::setUp();

        config([
            'koel.spotify.client_id' => 'fake-client-id',
            'koel.spotify.client_secret' => 'fake-client-secret',
        ]);

        $this->client = Mockery::mock(SpotifyClient::class);
        $this->service = new SpotifyService($this->client);
    }

    public function testTryGetArtistImage(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory(['name' => 'Foo'])->create();

        $this->client
            ->shouldReceive('search')
            ->with('Foo', 'artist', ['limit' => 1])
            ->andReturn(self::parseFixture('search-artist.json'));

        self::assertSame('https://foo/bar.jpg', $this->service->tryGetArtistImage($artist));
    }

    public function testTryGetArtistImageWhenServiceIsNotEnabled(): void
    {
        config(['koel.spotify.client_id' => null]);

        $this->client->shouldNotReceive('search');

        self::assertNull($this->service->tryGetArtistImage(Mockery::mock(Artist::class)));
    }

    public function testTryGetAlbumImage(): void
    {
        /** @var Album $album */
        $album = Album::factory(['name' => 'Bar'])->for(Artist::factory(['name' => 'Foo']))->create();

        $this->client
            ->shouldReceive('search')
            ->with('Bar artist:Foo', 'album', ['limit' => 1])
            ->andReturn(self::parseFixture('search-album.json'));

        self::assertSame('https://foo/bar.jpg', $this->service->tryGetAlbumCover($album));
    }

    public function testTryGetAlbumImageWhenServiceIsNotEnabled(): void
    {
        config(['koel.spotify.client_id' => null]);

        $this->client->shouldNotReceive('search');

        self::assertNull($this->service->tryGetAlbumCover(Mockery::mock(Album::class)));
    }

    /** @return array<mixed> */
    private static function parseFixture(string $name): array
    {
        return json_decode(File::get(test_path("blobs/spotify/$name")), true);
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
