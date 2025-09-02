<?php

namespace Database\Factories;

use App\Helpers\Ulid;
use App\Models\Artist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlbumFactory extends Factory
{
    /** @inheritdoc */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'artist_id' => Artist::factory(),
            'artist_name' => static fn (array $attributes) => Artist::query()->find($attributes['artist_id'])->name, // @phpstan-ignore-line
            'name' => $this->faker->colorName,
            'cover' => Ulid::generate() . '.jpg',
            'year' => $this->faker->year,
        ];
    }
}
