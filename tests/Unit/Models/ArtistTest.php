<?php

namespace Tests\Unit\Models;

use App\Models\Artist;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

use function Tests\test_path;

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
        self::assertNull(Artist::query()->where('name', 'Foo')->first());
        self::assertSame('Foo', Artist::getOrCreate('Foo')->name);
    }

    /** @return array<mixed> */
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
        $name = File::get(test_path('blobs/utf16'));
        $artist = Artist::getOrCreate($name);

        self::assertTrue(Artist::getOrCreate($name)->is($artist));
    }
}
