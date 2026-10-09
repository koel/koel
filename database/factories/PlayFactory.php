<?php

namespace Database\Factories;

use App\Models\Play;
use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Play> */
class PlayFactory extends Factory
{
    /** @inheritdoc */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'song_id' => Song::factory(),
            'played_at' => now(),
        ];
    }
}
