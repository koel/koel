<?php

namespace Database\Factories;

use App\Models\PlaylistFolder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PlaylistFolder> */
class PlaylistFolderFactory extends Factory
{
    /** @inheritdoc */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->name,
        ];
    }
}
