<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use Tests\TestCase;

class ExcerptSearchTest extends TestCase
{
    public function testSearch(): void
    {
        Song::factory()->create(['title' => 'A Foo Song']);
        Song::factory(6)->create();

        Artist::factory()->create(['name' => 'Foo Fighters']);
        Artist::factory(3)->create();

        Album::factory()->create(['name' => 'Foo Number Five']);
        Album::factory(4)->create();

        $this->getAs('api/search?q=foo')
            ->assertJsonStructure([
                'songs' => ['*' => SongTest::JSON_STRUCTURE],
                'artists' => ['*' => ArtistTest::JSON_STRUCTURE],
                'albums' => ['*' => AlbumTest::JSON_STRUCTURE],
            ]);
    }
}
