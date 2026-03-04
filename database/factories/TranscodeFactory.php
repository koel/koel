<?php

namespace Database\Factories;

use App\Models\Song;
use Illuminate\Database\Eloquent\Factories\Factory;

class TranscodeFactory extends Factory
{
    /** @inheritdoc  */
    public function definition(): array
    {
        return [
            'song_id' => Song::factory(),
            'bit_rate' => $this->faker->randomElement([128, 192, 256, 320]),
            'hash' => $this->faker->md5(),
            'location' => $this->faker->filePath() . '.mp4',
        ];
    }
}
