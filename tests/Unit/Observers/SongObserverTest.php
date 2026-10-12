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
}
