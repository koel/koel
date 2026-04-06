<?php

namespace Database\Factories;

use App\Enums\SongStorageType;
use App\Models\DuplicateUpload;
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
            'location' => '/var/media/koel/some-file.mp3',
            'storage' => SongStorageType::LOCAL,
        ];
    }
}
