<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Services\EncyclopediaService;
use App\Values\Artist\ArtistInformation;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtistInformationTest extends TestCase
{
    #[Test]
    public function getInformation(): void
    {
        config(['koel.services.lastfm.key' => 'foo']);
        config(['koel.services.lastfm.secret' => 'geheim']);

        /** @var Artist $artist */
        $artist = Artist::factory()->create();

        $lastfm = $this->mock(EncyclopediaService::class);
        $lastfm->expects('getArtistInformation')
            ->with(Mockery::on(static fn (Artist $a) => $a->is($artist)))
            ->andReturn(ArtistInformation::make(
                url: 'https://lastfm.com/artist/foo',
                image: 'https://lastfm.com/image/foo',
                bio: [
                    'summary' => 'foo',
                    'full' => 'bar',
                ],
            ));

        $this->getAs("api/artists/{$artist->id}/information")
            ->assertJsonStructure(ArtistInformation::JSON_STRUCTURE);
    }

    #[Test]
    public function getWithoutLastfmStillReturnsValidStructure(): void
    {
        config(['koel.services.lastfm.key' => null]);
        config(['koel.services.lastfm.secret' => null]);

        $this->getAs('api/artists/' . Artist::factory()->create()->id . '/information')
            ->assertJsonStructure(ArtistInformation::JSON_STRUCTURE);
    }
}
