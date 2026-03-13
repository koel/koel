<?php

namespace Database\Factories;

use App\Models\Playlist;
use App\Models\PlaylistCollaborationToken;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PlaylistCollaborationToken> */
class PlaylistCollaborationTokenFactory extends Factory
{
    /** @inheritdoc */
    public function definition(): array
    {
        return [
            'playlist_id' => Playlist::factory(),
        ];
    }
}
