<?php

namespace Tests\Unit\Models;

use App\Models\Album;
use App\Models\Artist;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlbumTest extends TestCase
{
    #[Test]
    public function existingAlbumCanBeRetrievedUsingArtistAndName(): void
    {
        $artist = Artist::factory()->createOne();
        $album = Album::factory()->for($artist)->for($artist->user)->createOne();

        self::assertTrue(Album::getOrCreate($artist, $album->name)->is($album));
    }

    #[Test]
    public function newAlbumIsAutomaticallyCreatedWithUserAndArtistAndName(): void
    {
        $artist = Artist::factory()->createOne();
        $name = 'Foo';

        self::assertNull(Album::query()->whereBelongsTo($artist)->where('name', $name)->first());

        $album = Album::getOrCreate($artist, $name);
        self::assertSame('Foo', $album->name);
        self::assertTrue($album->artist->is($artist));
    }

    /** @return array<mixed> */
    public static function provideEmptyAlbumNames(): array
    {
        return [
            [''],
            ['  '],
            [null],
            [false],
        ];
    }

    #[DataProvider('provideEmptyAlbumNames')]
    #[Test]
    public function newAlbumWithoutNameIsCreatedAsUnknownAlbum($name): void
    {
        $artist = Artist::factory()->createOne();

        $album = Album::getOrCreate($artist, $name);

        self::assertSame('Unknown Album', $album->name);
    }

    #[Test]
    public function getOrCreateReturnsExistingRowInsertedOutsideEloquent(): void
    {
        // A parallel scan chunk inserts via raw DB (or insertOrIgnore) without
        // firing Eloquent events. getOrCreate must still find that row instead
        // of trying to create a duplicate.
        $artist = Artist::factory()->createOne();
        $winnerId = (string) Str::ulid();

        DB::table('albums')->insert([
            'id' => $winnerId,
            'artist_id' => $artist->id,
            'artist_name' => $artist->name,
            'user_id' => $artist->user_id,
            'name' => 'Slave to the Grind',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $found = Album::getOrCreate($artist, 'Slave to the Grind');

        self::assertSame($winnerId, $found->id);
        self::assertSame(
            1,
            Album::query()->where('artist_id', $artist->id)->where('name', 'Slave to the Grind')->count(),
        );
    }

    #[Test]
    public function uniqueIndexRejectsDuplicateInserts(): void
    {
        $artist = Artist::factory()->createOne();
        Album::factory()->for($artist)->for($artist->user)->createOne(['name' => 'Slave to the Grind']);

        $this->expectException(UniqueConstraintViolationException::class);

        DB::table('albums')->insert([
            'id' => (string) Str::ulid(),
            'artist_id' => $artist->id,
            'artist_name' => $artist->name,
            'user_id' => $artist->user_id,
            'name' => 'Slave to the Grind',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
