<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Services\MediaInformationService;
use App\Values\AlbumInformation;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlbumInformationTest extends TestCase
{
    #[Test]
    public function getInformation(): void
    {
        config(['koel.services.lastfm.key' => 'foo']);
        config(['koel.services.lastfm.secret' => 'geheim']);

        $album = Album::factory()->create();

        $lastfm = $this->mock(MediaInformationService::class);
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

        $this->getAs("api/albums/{$album->public_id}/information")
            ->assertJsonStructure(AlbumInformation::JSON_STRUCTURE);
    }

    #[Test]
    public function getWithoutLastfmStillReturnsValidStructure(): void
    {
        config(['koel.services.lastfm.key' => null]);
        config(['koel.services.lastfm.secret' => null]);

        $this->getAs('api/albums/' . Album::factory()->create()->public_id . '/information')
            ->assertJsonStructure(AlbumInformation::JSON_STRUCTURE);
    }
}
