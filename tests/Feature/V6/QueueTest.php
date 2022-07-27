<?php

namespace Tests\Feature\V6;

use App\Models\Song;

class QueueTest extends TestCase
{
    public function testFetchSongs(): void
    {
        Song::factory(10)->create();

        $this->getAs('api/queue/fetch?order=rand&limit=5')
            ->assertJsonStructure(['*' => SongTest::JSON_STRUCTURE])
            ->assertJsonCount(5, '*');

        $this->getAs('api/queue/fetch?order=asc&sort=title&limit=5')
            ->assertJsonStructure(['*' => SongTest::JSON_STRUCTURE])
            ->assertJsonCount(5, '*');
    }
}
