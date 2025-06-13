<?php

namespace Database\Factories;

use App\Models\Artist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AlbumFactory extends Factory
{
    /** @inheritdoc */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'artist_id' => Artist::factory(),
            'name' => $this->faker->colorName,
            'cover' => Str::uuid()->toString() . '.jpg',
            'year' => $this->faker->year,
        ];
    }
}
