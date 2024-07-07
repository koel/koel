<?php

namespace Database\Factories;

use App\Models\Playlist;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlaylistCollaborationTokenFactory extends Factory
{
    /** @return array<mixed> */
    public function definition(): array
    {
        return [
            'playlist_id' => Playlist::factory(),
        ];
    }
}
