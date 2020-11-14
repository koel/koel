<?php

namespace Tests\Integration\Models;

use App\Models\Artist;
use Tests\TestCase;

class ArtistTest extends TestCase
{
    public function testExistingArtistCanBeRetrievedUsingName(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create(['name' => 'Foo']);

        self::assertTrue(Artist::getOrCreate('Foo')->is($artist));
    }

    public function testNewArtistIsCreatedWithName(): void
    {
        self::assertNull(Artist::whereName('Foo')->first());
        self::assertSame('Foo', Artist::getOrCreate('Foo')->name);
    }

    public function provideEmptyNames(): array
    {
        return [
            [''],
            ['  '],
            [null],
            [false],
        ];
    }

    /** @dataProvider provideEmptyNames */
    public function testGettingArtistWithEmptyNameReturnsUnknownArtist($name): void
    {
        self::assertTrue(Artist::getOrCreate($name)->is_unknown);
    }

    public function testArtistsWithNameInUtf16EncodingAreRetrievedCorrectly(): void
    {
        $name = file_get_contents(__DIR__.'../../../blobs/utf16');
        $artist = Artist::getOrCreate($name);

        self::assertTrue(Artist::getOrCreate($name)->is($artist));
    }
}
