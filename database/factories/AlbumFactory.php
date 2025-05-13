<?php

namespace Database\Factories;

use App\Models\Album;
use App\Models\Artist;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AlbumFactory extends Factory
{
    protected $model = Album::class;

    /** @inheritdoc */
    public function definition(): array
    {
        return [
            'artist_id' => Artist::factory(),
            'name' => $this->faker->colorName,
            'cover' => Str::uuid()->toString() . '.jpg',
            'year' => $this->faker->year,
        ];
    }
}
