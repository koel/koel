<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use App\Services\Lastfm;

class LastfmTest extends TestCase
{
    use DatabaseTransactions, WithoutMiddleware;

    public function testGetArtistInfo()
    {
        $client = \Mockery::mock(Client::class, [
            'get' => new Response(200, [], file_get_contents(dirname(__FILE__).'/blobs/lastfm/artist.xml')),
        ]);

        $api = new Lastfm(null, null, $client);

        $this->assertEquals([
            'url' => 'http://www.last.fm/music/Kamelot',
            'image' => 'http://foo.bar/extralarge.jpg',
            'bio' => [
                'summary' => 'Quisque ut nisi.',
                'full' => 'Quisque ut nisi. Vestibulum ullamcorper mauris at ligula.',
            ],
        ], $api->getArtistInfo('foo'));

        // Is it cached?
        $this->assertNotNull(Cache::get(md5('lastfm_artist_foo')));
    }

    public function testGetArtistInfoFailed()
    {
        $client = \Mockery::mock(Client::class, [
            'get' => new Response(400, [], file_get_contents(dirname(__FILE__).'/blobs/lastfm/artist-notfound.xml')),
        ]);

        $api = new Lastfm(null, null, $client);

        $this->assertFalse($api->getArtistInfo('foo'));
    }

    public function testGetAlbumInfo()
    {
        $client = \Mockery::mock(Client::class, [
            'get' => new Response(200, [], file_get_contents(dirname(__FILE__).'/blobs/lastfm/album.xml')),
        ]);

        $api = new Lastfm(null, null, $client);

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
        ], $api->getAlbumInfo('foo', 'bar'));

        // Is it cached?
        $this->assertNotNull(Cache::get(md5('lastfm_album_foo_bar')));
    }

    public function testGetAlbumInfoFailed()
    {
        $client = \Mockery::mock(Client::class, [
            'get' => new Response(400, [], file_get_contents(dirname(__FILE__).'/blobs/lastfm/album-notfound.xml')),
        ]);

        $api = new Lastfm(null, null, $client);

        $this->assertFalse($api->getAlbumInfo('foo', 'bar'));
    }
}
