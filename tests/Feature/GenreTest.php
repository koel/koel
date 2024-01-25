<?php

namespace Tests\Feature;

use App\Http\Resources\GenreResource;
use App\Http\Resources\SongResource;
use App\Models\Song;
use App\Values\Genre;
use Tests\TestCase;

class GenreTest extends TestCase
{
    public function testGetAllGenres(): void
    {
        Song::factory()->count(5)->create(['genre' => 'Rock']);
        Song::factory()->count(2)->create(['genre' => 'Pop']);
        Song::factory()->count(10)->create(['genre' => '']);

        $this->getAs('api/genres')
            ->assertJsonStructure(['*' => GenreResource::JSON_STRUCTURE])
            ->assertJsonFragment(['name' => 'Rock', 'song_count' => 5])
            ->assertJsonFragment(['name' => 'Pop', 'song_count' => 2])
            ->assertJsonFragment(['name' => Genre::NO_GENRE, 'song_count' => 10]);
    }

    public function testGetOneGenre(): void
    {
        Song::factory()->count(5)->create(['genre' => 'Rock']);

        $this->getAs('api/genres/Rock')
            ->assertJsonStructure(GenreResource::JSON_STRUCTURE)
            ->assertJsonFragment(['name' => 'Rock', 'song_count' => 5]);
    }

    public function testGetNonExistingGenreThrowsNotFound(): void
    {
        $this->getAs('api/genres/NonExistingGenre')->assertNotFound();
    }

    public function testPaginateSongsInGenre(): void
    {
        Song::factory()->count(5)->create(['genre' => 'Rock']);

        $this->getAs('api/genres/Rock/songs')
            ->assertJsonStructure(SongResource::PAGINATION_JSON_STRUCTURE);
    }

    public function testGetRandomSongsInGenre(): void
    {
        Song::factory()->count(5)->create(['genre' => 'Rock']);

        $this->getAs('api/genres/Rock/songs/random?limit=500')
            ->assertJsonStructure(['*' => SongResource::JSON_STRUCTURE]);
    }
}
