<?php

namespace Tests\Feature;

use App\Models\Artist;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtistEventTest extends TestCase
{
    #[Test]
    public function disabledInCommunityEdition(): void
    {
        $artist = Artist::factory()->createOne();

        $this->getAs("api/artists/{$artist->id}/events")->assertNotFound();
    }
}
