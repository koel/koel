<?php

namespace Tests\Integration\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Services\LastfmService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Log\Logger;
use Mockery;
use Tests\TestCase;

class LastfmServiceTest extends TestCase
{
    public function testGetArtistInformation(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->make(['name' => 'foo']);

        /** @var Client $client */
        $client = Mockery::mock(Client::class, [
            'get' => new Response(200, [], file_get_contents(__DIR__ . '../../../blobs/lastfm/artist.json')),
        ]);

        $api = new LastfmService($client, app(Cache::class), app(Logger::class));
        $info = $api->getArtistInformation($artist->name);

        self::assertEquals([
            'url' => 'https://www.last.fm/music/Kamelot',
            'image' => 'http://foo.bar/extralarge.jpg',
            'bio' => [
                'summary' => 'Quisque ut nisi.',
                'full' => 'Quisque ut nisi. Vestibulum ullamcorper mauris at ligula.',
            ],
        ], $info);

        self::assertNotNull(cache()->get('0aff3bc1259154f0e9db860026cda7a6'));
    }

    public function testGetArtistInformationForNonExistentArtist(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->make();

        /** @var Client $client */
        $client = Mockery::mock(Client::class, [
            'get' => new Response(400, [], file_get_contents(__DIR__ . '../../../blobs/lastfm/artist-notfound.json')),
        ]);

        $api = new LastfmService($client, app(Cache::class), app(Logger::class));

        self::assertNull($api->getArtistInformation($artist->name));
    }

    public function testGetAlbumInformation(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create([
            'artist_id' => Artist::factory()->create(['name' => 'bar'])->id,
            'name' => 'foo',
        ]);

        /** @var Client $client */
        $client = Mockery::mock(Client::class, [
            'get' => new Response(200, [], file_get_contents(__DIR__ . '../../../blobs/lastfm/album.json')),
        ]);

        $api = new LastfmService($client, app(Cache::class), app(Logger::class));
        $info = $api->getAlbumInformation($album->name, $album->artist->name);

        self::assertEquals([
            'url' => 'https://www.last.fm/music/Kamelot/Epica',
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

        self::assertNotNull(cache()->get('fca889d13b3222589d7d020669cc5a38'));
    }

    public function testGetAlbumInformationForNonExistentAlbum(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        /** @var Client $client */
        $client = Mockery::mock(Client::class, [
            'get' => new Response(400, [], file_get_contents(__DIR__ . '../../../blobs/lastfm/album-notfound.json')),
        ]);

        $api = new LastfmService($client, app(Cache::class), app(Logger::class));

        self::assertNull($api->getAlbumInformation($album->name, $album->artist->name));
    }
}
