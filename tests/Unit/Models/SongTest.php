<?php

namespace Tests\Unit\Models;

use App\Models\Song;
use Tests\TestCase;

class SongTest extends TestCase
{
    public function testGettingS3HostedSongs(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create(['path' => 's3://foo/bar']);

        self::assertEquals(['bucket' => 'foo', 'key' => 'bar'], $song->s3_params);
    }

    public function testLyricsDoNotContainTimestamps(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create(['lyrics' => "[00:00.00]Line 1\n[00:01.00]Line 2\n[00:02.00]Line 3"]);

        self::assertSame("Line 1\nLine 2\nLine 3", $song->lyrics);
    }
}
