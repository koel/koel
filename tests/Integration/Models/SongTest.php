<?php

namespace Tests\Integration\Models;

use App\Models\Song;
use Aws\AwsClient;
use Cache;
use Mockery as m;
use Tests\TestCase;

class SongTest extends TestCase
{
    /** @test */
    public function it_can_be_retrieved_using_its_path()
    {
        // Given a song with a path
        /** @var Song $song */
        $song = factory(Song::class)->create(['path' => 'foo']);

        // When I retrieve it using the path
        $retrieved = Song::byPath('foo');

        // Then the song is retrieved
        $this->assertEquals($song->id, $retrieved->id);
    }

    /** @test */
    public function its_lyrics_has_all_new_line_characters_replace_by_br_tags()
    {
        // Given a song with lyrics contains new line characters
        /** @var Song $song */
        $song = factory(Song::class)->create([
            'lyrics' => "foo\rbar\nbaz\r\nqux",
        ]);

        // When I retrieve its lyrics
        $lyrics = $song->lyrics;

        // Then I see the new line characters replaced by <br />
        $this->assertEquals('foo<br />bar<br />baz<br />qux', $lyrics);
    }

    /** @test */
    public function amazon_s3_parameters_can_be_retrieved_from_s3_hosted_songs()
    {
        // Given a song hosted on S3
        /** @var Song $song */
        $song = factory(Song::class)->create(['path' => 's3://foo/bar']);

        // When I check its S3 parameters
        $params = $song->s3_params;

        // Then I receive the correct parameters
        $this->assertEquals(['bucket' => 'foo', 'key' => 'bar'], $params);
    }
}
