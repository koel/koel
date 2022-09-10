<?php

namespace Database\Factories;

use App\Models\PlaylistFolder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlaylistFolderFactory extends Factory
{
    protected $model = PlaylistFolder::class;

    /** @return array<mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->name,
        ];
    }
}
