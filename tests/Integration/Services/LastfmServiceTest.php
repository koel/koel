<?php

namespace Tests\Integration\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Models\User;
use App\Services\LastfmService;
use App\Services\UserPreferenceService;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Log\Logger;
use Mockery;
use Tests\TestCase;

class LastfmServiceTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testGetArtistInformation(): void
    {
        /** @var Artist $artist */
        $artist = factory(Artist::class)->make(['name' => 'foo']);

        /** @var Client $client */
        $client = Mockery::mock(Client::class, [
            'get' => new Response(200, [], file_get_contents(__DIR__.'../../../blobs/lastfm/artist.xml')),
        ]);

        $info = $this->createServiceWithMockedClient($client)->getArtistInformation($artist->name);

        $this->assertEquals([
            'url' => 'http://www.last.fm/music/Kamelot',
            'image' => 'http://foo.bar/extralarge.jpg',
            'bio' => [
                'summary' => 'Quisque ut nisi.',
                'full' => 'Quisque ut nisi. Vestibulum ullamcorper mauris at ligula.',
            ],
        ], $info);

        self::assertNotNull(cache('0aff3bc1259154f0e9db860026cda7a6'));
    }

    public function testGetArtistInformationForNonExistentArtist(): void
    {
        /** @var Artist $artist */
        $artist = factory(Artist::class)->make();

        /** @var Client $client */
        $client = Mockery::mock(Client::class, [
            'get' => new Response(400, [], file_get_contents(__DIR__.'../../../blobs/lastfm/artist-notfound.xml')),
        ]);

        self::assertNull($this->createServiceWithMockedClient($client)->getArtistInformation($artist->name));
    }

    /**
     * @throws Exception
     */
    public function testGetAlbumInformation(): void
    {
        /** @var Album $album */
        $album = factory(Album::class)->create([
            'artist_id' => factory(Artist::class)->create(['name' => 'bar'])->id,
            'name' => 'foo',
        ]);

        /** @var Client $client */
        $client = Mockery::mock(Client::class, [
            'get' => new Response(200, [], file_get_contents(__DIR__.'../../../blobs/lastfm/album.xml')),
        ]);

        $info = $this->createServiceWithMockedClient($client)->getAlbumInformation($album->name, $album->artist->name);

        // Then I get the album's info
        $this->assertEquals([
            'url' => 'http://www.last.fm/music/Kamelot/Epica',
            'image' => 'http://foo.bar/extralarge.jpg',
            'tracks' => [
                [
                    'title' => 'Track 1',
                    'url' => 'http://foo/track1',
                    'length' => 100,
                ],
                [
                    'title' => 'Track 2',
                    'url' => 'http://foo/track2',
                    'length' => 150,
                ],
            ],
            'wiki' => [
                'summary' => 'Quisque ut nisi.',
                'full' => 'Quisque ut nisi. Vestibulum ullamcorper mauris at ligula.',
            ],
        ], $info);

        self::assertNotNull(cache('fca889d13b3222589d7d020669cc5a38'));
    }

    public function testGetAlbumInformationForNonExistentAlbum(): void
    {
        /** @var Album $album */
        $album = factory(Album::class)->create();

        /** @var Client $client */
        $client = Mockery::mock(Client::class, [
            'get' => new Response(400, [], file_get_contents(__DIR__.'../../../blobs/lastfm/album-notfound.xml')),
        ]);

        $api = $this->createServiceWithMockedClient($client);

        self::assertNull($api->getAlbumInformation($album->name, $album->artist->name));
    }

    public function testFetchSessionKeyUsingToken(): void
    {
        /** @var Client $client */
        $client = Mockery::mock(Client::class, [
            'get' => new Response(200, [], file_get_contents(__DIR__.'../../../blobs/lastfm/session-key.xml')),
        ]);

        self::assertEquals('foo', $this->createServiceWithMockedClient($client)->fetchSessionKeyUsingToken('bar'));
    }

    public function testIsUserConnected(): void
    {
        $user = factory(User::class)->create([
            'preferences' => ['lastfm_session_key' => 'foo'],
        ]);

        self::assertTrue(app(LastfmService::class)->isUserConnected($user));

        $user = factory(User::class)->create([
            'preferences' => [],
        ]);

        self::assertFalse(app(LastfmService::class)->isUserConnected($user));
    }

    private function createServiceWithMockedClient(Client $client): LastfmService
    {
        return new LastfmService($client, app(Cache::class), app(Logger::class), app(UserPreferenceService::class));
    }
}
