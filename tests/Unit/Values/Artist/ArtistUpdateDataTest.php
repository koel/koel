<?php

namespace Tests\Unit\Values\Artist;

use App\Values\Artist\ArtistUpdateData;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ArtistUpdateDataTest extends TestCase
{
    #[Test]
    public function keyTheImageByTheArtistsImageColumn(): void
    {
        self::assertSame(
            ['name' => 'Metallica', 'image' => 'data:image/png;base64,abc'],
            ArtistUpdateData::make(name: 'Metallica', image: 'data:image/png;base64,abc')->toArray(),
        );
    }
}
