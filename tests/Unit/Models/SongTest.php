<?php

namespace Tests\Unit\Models;

use App\Models\Interaction;
use App\Models\Song;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function testSongHasManyRelationshipWithInteractions()
    {
        /** @var Song $song */
        $song = Song::factory()->create();

        /** @var Interaction $interactions */
        $interactions = Interaction::factory(3, ['song_id' => $song->id])->create();

        $this->assertEquals($song->interactions->count(), $interactions->count());

        $this->assertInstanceOf(Interaction::class, $song->interactions()->first());

        $this->assertInstanceOf(HasMany::class, $song->interactions());

        $this->assertInstanceOf(Song::class , $interactions->first()->song);
    }
}
