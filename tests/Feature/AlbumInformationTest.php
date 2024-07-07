<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Services\MediaInformationService;
use App\Values\AlbumInformation;
use Mockery;
use Tests\TestCase;

class AlbumInformationTest extends TestCase
{
    public function testGet(): void
    {
        config(['koel.lastfm.key' => 'foo']);
        config(['koel.lastfm.secret' => 'geheim']);

        $album = Album::factory()->create();

        $lastfm = self::mock(MediaInformationService::class);
        $lastfm->shouldReceive('getAlbumInformation')
            ->with(Mockery::on(static fn (Album $a) => $a->is($album)))
            ->andReturn(AlbumInformation::make(
                url: 'https://lastfm.com/album/foo',
                cover: 'https://lastfm.com/cover/foo',
                wiki: [
                    'summary' => 'foo',
                    'full' => 'bar',
                ],
                tracks: [
                    [
                        'title' => 'foo',
                        'length' => 123,
                        'url' => 'https://lastfm.com/track/foo',
                    ],
                    [
                        'title' => 'bar',
                        'length' => 456,
                        'url' => 'https://lastfm.com/track/bar',
                    ],
                ]
            ));

        $this->getAs('api/albums/' . $album->id . '/information')
            ->assertJsonStructure(AlbumInformation::JSON_STRUCTURE);
    }

    public function testGetWithoutLastfmStillReturnsValidStructure(): void
    {
        config(['koel.lastfm.key' => null]);
        config(['koel.lastfm.secret' => null]);

        $this->getAs('api/albums/' . Album::factory()->create()->id . '/information')
            ->assertJsonStructure(AlbumInformation::JSON_STRUCTURE);
    }
}
