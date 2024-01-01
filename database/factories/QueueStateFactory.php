<?php

namespace Database\Factories;

use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QueueStateFactory extends Factory
{
    /** @return array<mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'song_ids' => Song::factory()->count(3)->create()->pluck('id')->toArray(),
            'current_song_id' => null,
            'playback_position' => 0,
        ];
    }
}
