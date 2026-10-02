<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Values\Artist\ArtistInformation;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtistInformationTest extends TestCase
{
    #[Test]
    public function getWithoutLastfmStillReturnsValidStructure(): void
    {
        config(['koel.services.lastfm.key' => null]);
        config(['koel.services.lastfm.secret' => null]);

        $this->getAs(
            'api/artists/' . Artist::factory()->createOne()->id . '/information',
        )->assertJsonStructure(ArtistInformation::JSON_STRUCTURE);
    }
}
