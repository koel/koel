<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Values\Album\AlbumInformation;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlbumInformationTest extends TestCase
{
    #[Test]
    public function getWithoutLastfmStillReturnsValidStructure(): void
    {
        config(['koel.services.lastfm.key' => null]);
        config(['koel.services.lastfm.secret' => null]);

        $this->getAs(
            'api/albums/' . Album::factory()->createOne()->id . '/information',
        )->assertJsonStructure(AlbumInformation::JSON_STRUCTURE);
    }
}
