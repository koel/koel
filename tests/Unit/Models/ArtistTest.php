<?php

namespace Tests\Unit\Models;

use App\Models\Artist;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;
use function Tests\test_path;

class ArtistTest extends TestCase
{
    #[Test]
    public function existingArtistCanBeRetrievedUsingName(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create(['name' => 'Foo']);

        self::assertTrue(Artist::getOrCreate($artist->user, 'Foo')->is($artist));
    }

    #[Test]
    public function newArtistIsCreatedWithName(): void
    {
        self::assertNull(Artist::query()->where('name', 'Foo')->first());
        self::assertSame('Foo', Artist::getOrCreate(create_user(), 'Foo')->name);
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
        self::assertTrue(Artist::getOrCreate(create_user(), $name)->is_unknown);
    }

    #[Test]
    public function artistsWithNameInUtf16EncodingAreRetrievedCorrectly(): void
    {
        $name = File::get(test_path('fixtures/utf16'));
        $artist = Artist::getOrCreate(create_user(), $name);

        self::assertTrue(Artist::getOrCreate($artist->user, $name)->is($artist));
    }
}
