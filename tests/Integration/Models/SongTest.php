<?php

namespace Tests\Integration\Models;

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
}
