<?php

namespace Tests\Unit\Models;

use App\Models\Genre;
use App\Models\Song;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SongTest extends TestCase
{
    #[Test]
    public function retrievedLyricsDoNotContainTimestamps(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create(['lyrics' => "[00:00.00]Line 1\n[00:01.00]Line 2\n[00:02.00]Line 3"]);

        self::assertSame("Line 1\nLine 2\nLine 3", $song->lyrics);
        self::assertSame("[00:00.00]Line 1\n[00:01.00]Line 2\n[00:02.00]Line 3", $song->getAttributes()['lyrics']);
    }

    #[Test]
    public function syncGenres(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create();
        $song->syncGenres('Pop, Rock');

        self::assertCount(2, $song->genres);
        self::assertEqualsCanonicalizing(['Pop', 'Rock'], $song->genres->pluck('name')->all());
    }

    /** @return array<mixed> */
    public static function provideGenreData(): array
    {
        return [
            ['Rock, Pop', true],
            ['Pop, Rock', true],
            ['Rock,   Pop ', true],
            ['Rock', false],
            ['Jazz, Pop', false],
        ];
    }

    #[Test]
    #[DataProvider('provideGenreData')]
    public function genreEqualsTo(string $target, bool $isEqual): void
    {
        /** @var Song $song */
        $song = Song::factory()
            ->hasAttached(Genre::factory()->create(['name' => 'Pop']))
            ->hasAttached(Genre::factory()->create(['name' => 'Rock']))
            ->create();

        self::assertSame($isEqual, $song->genreEqualsTo($target));
    }

    #[Test]
    public function deletingByChunk(): void
    {
        Song::factory(5)->create();

        Song::deleteByChunk(Song::query()->get()->modelKeys(), 1);

        self::assertSame(0, Song::query()->count());
    }
}
