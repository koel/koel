<?php

namespace Tests\Unit\KoelPlus\Models;

use App\Models\Album;
use App\Models\Artist;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

class AlbumTest extends PlusTestCase
{
    #[Test]
    public function getOrCreate(): void
    {
        $artist = Artist::factory()->createOne();
        $album = Album::factory()
            ->for($artist)
            ->for($artist->user)
            ->createOne(['name' => 'Foo']);

        // The album can be retrieved by its artist and user
        self::assertTrue(Album::getOrCreate($album->artist, 'Foo')->is($album));

        // Calling getOrCreate with a different artist should return another album
        self::assertFalse(Album::getOrCreate(Artist::factory()->createOne(), 'Foo')->is($album));
    }
}
