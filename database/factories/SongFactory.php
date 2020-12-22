<?php

namespace Database\Factories;

use App\Models\Album;
use App\Models\Song;
use Illuminate\Database\Eloquent\Factories\Factory;

class SongFactory extends Factory
{
    protected $model = Song::class;

    /** @return array<mixed> */
    public function definition(): array
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        return [
            'album_id' => $album->id,
            'artist_id' => $album->artist->id,
            'title' => ucwords($this->faker->words(random_int(2, 5), true)),
            'length' => $this->faker->randomFloat(2, 10, 500),
            'track' => random_int(1, 20),
            'lyrics' => $this->faker->paragraph(),
            'path' => '/tmp/' . uniqid() . '.mp3',
            'mtime' => time(),
        ];
    }
}
