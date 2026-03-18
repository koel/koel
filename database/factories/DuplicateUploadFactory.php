<?php

namespace Database\Factories;

use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DuplicateUpload> */
class DuplicateUploadFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'existing_song_id' => Song::factory(),
            'file_path' => '/tmp/duplicate_uploads/' . $this->faker->uuid() . '.mp3',
        ];
    }
}
