<?php

namespace Tests\Unit\Providers;

use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MacroProviderTest extends TestCase
{
    #[Test]
    public function getWhereInChunksReturnsEveryMatchOnceAcrossChunks(): void
    {
        $songIds = Song::factory()->createMany(5)->modelKeys();
        Song::factory()->createOne();

        $songs = Song::query()->getWhereInChunks('songs.id', [...$songIds, $songIds[0]], chunkSize: 2);

        self::assertEqualsCanonicalizing($songIds, $songs->modelKeys());
    }

    #[Test]
    public function countWhereInChunksCountsEveryMatchOnceAcrossChunks(): void
    {
        $songIds = Song::factory()->createMany(5)->modelKeys();
        Song::factory()->createOne();

        $count = Song::query()->countWhereInChunks('songs.id', [...$songIds, $songIds[0], 'missing'], chunkSize: 2);

        self::assertSame(5, $count);
    }

    #[Test]
    public function orderByArraySortsByFirstPositionOfEachKey(): void
    {
        $songs = Song::factory()->createMany(3);
        [$first, $second, $third] = $songs->modelKeys();

        $ordered = $songs->shuffle()->orderByArray([$third, $first, $third, $second]);

        self::assertSame([$third, $first, $second], $ordered->modelKeys());
    }
}
