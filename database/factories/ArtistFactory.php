<?php

namespace Database\Factories;

use App\Helpers\Ulid;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArtistFactory extends Factory
{
    /** @inheritdoc */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->name,
            'image' => Ulid::generate() . '.jpg',
        ];
    }
}
