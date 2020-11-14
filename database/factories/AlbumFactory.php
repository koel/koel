<?php

namespace Database\Factories;

use App\Models\Album;
use App\Models\Artist;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlbumFactory extends Factory
{
    protected $model = Album::class;

    public function definition(): array
    {
        return [
            'artist_id' => Artist::factory(),
            'name' => ucwords($this->faker->words(random_int(2, 5), true)),
            'cover' => md5(uniqid()).'.jpg',
        ];
    }
}
