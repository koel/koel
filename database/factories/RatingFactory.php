<?php

namespace Database\Factories;

use App\Models\Rating;
use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Rating> */
class RatingFactory extends Factory
{
    /** @inheritdoc */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'rateable_id' => Song::factory(),
            'rateable_type' => (new Song())->getMorphClass(),
            'rating' => fake()->numberBetween(1, 5),
        ];
    }
}
