<?php

namespace Database\Factories;

use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InteractionFactory extends Factory
{
    /** @inheritdoc */
    public function definition(): array
    {
        return [
            'song_id' => Song::factory(),
            'user_id' => User::factory(),
            'play_count' => $this->faker->randomNumber(),
            'last_played_at' => now(),
        ];
    }
}
