<?php

namespace Tests\Feature;

use App\Http\Resources\SongResource;
use App\Models\Song;
use Tests\TestCase;

class SongSearchTest extends TestCase
{
    public function testSearch(): void
    {
        Song::factory(10)->create(['title' => 'A Foo Song']);

        $this->getAs('api/search/songs?q=foo')
            ->assertJsonStructure(['*' => SongResource::JSON_STRUCTURE]);
    }
}
