<?php

namespace Tests\Unit\Models;

use App\Models\Song;
use Tests\TestCase;

class SongTest extends TestCase
{
    public function testRetrievedLyricsDoNotContainTimestamps(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create(['lyrics' => "[00:00.00]Line 1\n[00:01.00]Line 2\n[00:02.00]Line 3"]);

        self::assertSame("Line 1\nLine 2\nLine 3", $song->lyrics);
        self::assertSame("[00:00.00]Line 1\n[00:01.00]Line 2\n[00:02.00]Line 3", $song->getAttributes()['lyrics']);
    }
}
