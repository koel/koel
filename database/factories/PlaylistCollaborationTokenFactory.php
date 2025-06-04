<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

use function Tests\create_playlist;

class PlaylistCollaborationTokenFactory extends Factory
{
    /** @inheritdoc */
    public function definition(): array
    {
        return [
            'playlist_id' => create_playlist()->id,
        ];
    }
}
