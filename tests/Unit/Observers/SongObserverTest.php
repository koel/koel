<?php

namespace Tests\Unit\Observers;

use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SongObserverTest extends TestCase
{
    #[Test]
    public function setPathHashOnCreate(): void
    {
        $song = Song::factory()->createOne(['path' => '/music/foo.mp3']);

        self::assertSame(simple_hash('/music/foo.mp3'), $song->refresh()->path_hash);
    }

    #[Test]
    public function updatePathHashWhenPathChanges(): void
    {
        $song = Song::factory()->createOne(['path' => '/music/foo.mp3']);

        $song->update(['path' => '/music/bar.mp3']);

        self::assertSame(simple_hash('/music/bar.mp3'), $song->refresh()->path_hash);
    }

    #[Test]
    public function setSortTitleOnCreate(): void
    {
        $song = Song::factory()->createOne(['title' => 'The Song']);

        self::assertSame('Song, The', $song->refresh()->title_sort);
    }

    #[Test]
    public function updateSortTitleWhenTitleChanges(): void
    {
        $song = Song::factory()->createOne(['title' => 'The Song']);

        $song->update(['title' => 'A Better Song']);

        self::assertSame('Better Song, A', $song->refresh()->title_sort);
    }

    #[Test]
    public function keepTaggedSortTitleWhenTitleChanges(): void
    {
        $song = Song::factory()->createOne(['title' => 'The Song', 'title_sort' => 'Tagged Sort Title']);

        $song->update(['title' => 'A Better Song']);

        self::assertSame('Tagged Sort Title', $song->refresh()->title_sort);
    }
}
