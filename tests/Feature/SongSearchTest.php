<?php

namespace Tests\Feature;

use App\Http\Resources\SongResource;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SongSearchTest extends TestCase
{
    #[Test]
    public function search(): void
    {
        Song::factory(10)->create(['title' => 'A Foo Song']);

        $this->getAs('api/search/songs?q=foo')
            ->assertJsonStructure(['*' => SongResource::JSON_STRUCTURE]);
    }
}
