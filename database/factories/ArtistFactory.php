<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ArtistFactory extends Factory
{
    /** @return array<mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->name,
            'image' => Str::uuid() . '.jpg',
        ];
    }
}
