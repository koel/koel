<?php

namespace Tests\Unit\Models;

use App\Models\Artist;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
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
        $artist = Artist::factory()->createOne(['name' => 'Foo']);

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

    #[Test]
    public function getOrCreateReturnsExistingRowInsertedOutsideEloquent(): void
    {
        // A parallel scan chunk inserts via raw DB (or insertOrIgnore) without
        // firing Eloquent events. getOrCreate must still find that row instead
        // of trying to create a duplicate.
        $user = create_user();
        $winnerId = (string) Str::ulid();

        DB::table('artists')->insert([
            'id' => $winnerId,
            'user_id' => $user->id,
            'name' => 'Skid Row',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $found = Artist::getOrCreate($user, 'Skid Row');

        self::assertSame($winnerId, $found->id);
        self::assertSame(1, Artist::query()->where('user_id', $user->id)->where('name', 'Skid Row')->count());
    }

    #[Test]
    public function uniqueIndexRejectsDuplicateInserts(): void
    {
        $user = create_user();
        Artist::factory()->for($user)->createOne(['name' => 'Pearl Jam']);

        $this->expectException(UniqueConstraintViolationException::class);

        DB::table('artists')->insert([
            'id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'name' => 'Pearl Jam',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    #[Test]
    public function nameAccessorIsNullSafe(): void
    {
        // Scout's database engine calls toSearchableArray() on a fresh model instance
        // to introspect searchable columns; that read of $this->name hits the accessor
        // with a null value.
        self::assertSame(Artist::UNKNOWN_NAME, (new Artist())->name);
    }
}
