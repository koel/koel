<?php

namespace Tests\Unit\Services;

use App\Http\Integrations\Spotify\SpotifyClient;
use App\Models\Album;
use App\Models\Artist;
use App\Services\SpotifyService;
use Illuminate\Support\Facades\File;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\test_path;

class SpotifyServiceTest extends TestCase
{
    private SpotifyService $service;
    private SpotifyClient|MockInterface $client;

    public function setUp(): void
    {
        parent::setUp();

        config([
            'koel.services.spotify.client_id' => 'fake-client-id',
            'koel.services.spotify.client_secret' => 'fake-client-secret',
        ]);

        $this->client = Mockery::mock(SpotifyClient::class);
        $this->service = new SpotifyService($this->client);
    }

    #[Test]
    public function tryGetArtistImage(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory(['name' => 'Foo'])->create();

        $this->client
            ->expects('search')
            ->with('Foo', 'artist', ['limit' => 1])
            ->andReturn(self::parseFixture('search-artist.json'));

        self::assertSame('https://foo/bar.jpg', $this->service->tryGetArtistImage($artist));
    }

    #[Test]
    public function tryGetArtistImageWhenServiceIsNotEnabled(): void
    {
        config(['koel.services.spotify.client_id' => null]);

        $this->client->shouldNotReceive('search');

        self::assertNull($this->service->tryGetArtistImage(Mockery::mock(Artist::class)));
    }

    #[Test]
    public function tryGetAlbumImage(): void
    {
        /** @var Album $album */
        $album = Album::factory(['name' => 'Bar'])->for(Artist::factory(['name' => 'Foo']))->create();

        $this->client
            ->expects('search')
            ->with('Bar artist:Foo', 'album', ['limit' => 1])
            ->andReturn(self::parseFixture('search-album.json'));

        self::assertSame('https://foo/bar.jpg', $this->service->tryGetAlbumCover($album));
    }

    #[Test]
    public function tryGetAlbumImageWhenServiceIsNotEnabled(): void
    {
        config(['koel.services.spotify.client_id' => null]);

        $this->client->shouldNotReceive('search');

        self::assertNull($this->service->tryGetAlbumCover(Mockery::mock(Album::class)));
    }

    /** @return array<mixed> */
    private static function parseFixture(string $name): array
    {
        return File::json(test_path("fixtures/spotify/$name"));
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
