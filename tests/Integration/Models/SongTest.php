<?php

namespace Tests\Integration\Models;

use App\Models\Song;
use Tests\TestCase;

class SongTest extends TestCase
{
    public function testLyricsHaveNewlinesReplacedByBrTags(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create([
            'lyrics' => "foo\rbar\nbaz\r\nqux",
        ]);

        self::assertEquals('foo<br />bar<br />baz<br />qux', $song->lyrics);
    }

    public function testGettingS3HostedSongs(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create(['path' => 's3://foo/bar']);

        self::assertEquals(['bucket' => 'foo', 'key' => 'bar'], $song->s3_params);
    }
}
