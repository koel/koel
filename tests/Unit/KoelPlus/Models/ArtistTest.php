<?php

namespace Tests\Unit\KoelPlus\Models;

use App\Models\Artist;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class ArtistTest extends PlusTestCase
{
    #[Test]
    public function getOrCreate(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create(['name' => 'Foo']);

        // The artist can be retrieved by its name and user
        self::assertTrue(Artist::getOrCreate($artist->user, 'Foo')->is($artist));

        // Calling getOrCreate with a different user should return another artist
        self::assertFalse(Artist::getOrCreate(create_user(), 'Foo')->is($artist));
    }
}
