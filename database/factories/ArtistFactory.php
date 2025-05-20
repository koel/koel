<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ArtistFactory extends Factory
{
    /** @return array<mixed> */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'image' => Str::uuid() . '.jpg',
        ];
    }
}
