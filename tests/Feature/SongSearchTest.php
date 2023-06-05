<?php

namespace Tests\Feature;

use App\Models\Song;

class SongSearchTest extends TestCase
{
    public function testSearch(): void
    {
        Song::factory(10)->create(['title' => 'A Foo Song']);

        $this->getAs('api/search/songs?q=foo')
            ->assertJsonStructure(['*' => SongTest::JSON_STRUCTURE]);
    }
}
