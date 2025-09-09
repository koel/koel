<?php

namespace Database\Factories;

use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QueueStateFactory extends Factory
{
    /** @inheritdoc */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'song_ids' => Song::factory()->count(3)->create()->modelKeys(),
            'current_song_id' => null,
            'playback_position' => 0,
        ];
    }
}
