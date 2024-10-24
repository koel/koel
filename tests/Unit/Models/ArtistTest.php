<?php

namespace Tests\Unit\Models;

use App\Models\Artist;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\test_path;

class ArtistTest extends TestCase
{
    #[Test]
    public function existingArtistCanBeRetrievedUsingName(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create(['name' => 'Foo']);

        self::assertTrue(Artist::getOrCreate('Foo')->is($artist));
    }

    #[Test]
    public function newArtistIsCreatedWithName(): void
    {
        self::assertNull(Artist::query()->where('name', 'Foo')->first());
        self::assertSame('Foo', Artist::getOrCreate('Foo')->name);
    }

    /** @return array<mixed> */
    public static function provideEmptyNames(): array
    {
        return [
            [''],
            ['  '],
            [null],
            [false],
        ];
    }

    #[DataProvider('provideEmptyNames')]
    #[Test]
    public function gettingArtistWithEmptyNameReturnsUnknownArtist($name): void
    {
        self::assertTrue(Artist::getOrCreate($name)->is_unknown);
    }

    #[Test]
    public function artistsWithNameInUtf16EncodingAreRetrievedCorrectly(): void
    {
        $name = File::get(test_path('blobs/utf16'));
        $artist = Artist::getOrCreate($name);

        self::assertTrue(Artist::getOrCreate($name)->is($artist));
    }
}
