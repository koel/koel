<?php

namespace Tests\Integration\Services;

use App\Models\Album;
use App\Models\Artist;
use App\Services\Lastfm;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mockery as m;
use Tests\TestCase;

class LastfmTest extends TestCase
{
    protected function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_returns_artist_info_if_artist_is_found_on_lastfm()
    {
        // Given an artist that exists on Last.fm
        /** @var Artist $artist */
        $artist = factory(Artist::class)->create(['name' => 'foo']);

        // When I request the service for the artist's info
        $client = m::mock(Client::class, [
            'get' => new Response(200, [], file_get_contents(__DIR__.'../../../blobs/lastfm/artist.xml')),
        ]);

        $api = new Lastfm(null, null, $client);
        $info = $api->getArtistInfo($artist->name);

        // Then I see the info when the request is the successful
        $this->assertEquals([
            'url' => 'http://www.last.fm/music/Kamelot',
            'image' => 'http://foo.bar/extralarge.jpg',
            'bio' => [
                'summary' => 'Quisque ut nisi.',
                'full' => 'Quisque ut nisi. Vestibulum ullamcorper mauris at ligula.',
            ],
        ], $info);

        // And the response XML is cached as well
        $this->assertNotNull(cache('0aff3bc1259154f0e9db860026cda7a6'));
    }

    /** @test */
    public function it_returns_false_if_artist_info_is_not_found_on_lastfm()
    {
        // Given an artist that doesn't exist on Last.fm
        /** @var Artist $artist */
        $artist = factory(Artist::class)->create();

        // When I request the service for the artist info
        $client = m::mock(Client::class, [
            'get' => new Response(400, [], file_get_contents(__DIR__.'../../../blobs/lastfm/artist-notfound.xml')),
        ]);

        $api = new Lastfm(null, null, $client);
        $result = $api->getArtistInfo($artist->name);

        // Then I receive boolean false
        $this->assertFalse($result);
    }

    /**
     * @test
     *
     * @throws \Exception
     */
    public function it_returns_album_info_if_album_is_found_on_lastfm()
    {
        // Given an album that exists on Last.fm
        /** @var Album $album */
        $album = factory(Album::class)->create([
            'artist_id' => factory(Artist::class)->create(['name' => 'bar'])->id,
            'name' => 'foo',
        ]);

        // When I request the service for the album's info
        $client = m::mock(Client::class, [
            'get' => new Response(200, [], file_get_contents(__DIR__.'../../../blobs/lastfm/album.xml')),
        ]);

        $api = new Lastfm(null, null, $client);
        $info = $api->getAlbumInfo($album->name, $album->artist->name);

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

        // And the response is cached
        $this->assertNotNull(cache('fca889d13b3222589d7d020669cc5a38'));
    }

    /** @test */
    public function it_returns_false_if_album_info_is_not_found_on_lastfm()
    {
        // Given there's an album which doesn't exist on Last.fm
        $album = factory(Album::class)->create();

        // When I request the service for the album's info
        $client = m::mock(Client::class, [
            'get' => new Response(400, [], file_get_contents(__DIR__.'../../../blobs/lastfm/album-notfound.xml')),
        ]);

        $api = new Lastfm(null, null, $client);
        $result = $api->getAlbumInfo($album->name, $album->artist->name);

        // Then I receive a boolean false
        $this->assertFalse($result);
    }
}
