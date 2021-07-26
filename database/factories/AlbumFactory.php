<?php

namespace Database\Factories;

use App\Models\Album;
use App\Models\Artist;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlbumFactory extends Factory
{
    protected $model = Album::class;

    /** @return array<mixed> */
    public function definition(): array
    {
        return [
            'artist_id' => Artist::factory(),
            'name' => $this->faker->colorName,
            'cover' => md5(uniqid()) . '.jpg',
        ];
    }
}
