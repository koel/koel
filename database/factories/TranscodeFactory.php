<?php

namespace Database\Factories;

use App\Enums\TranscodeCodec;
use App\Models\Song;
use App\Models\Transcode;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Transcode> */
class TranscodeFactory extends Factory
{
    /** @inheritdoc  */
    public function definition(): array
    {
        return [
            'song_id' => Song::factory(),
            'bit_rate' => fake()->randomElement([128, 192, 256, 320]),
            'codec' => TranscodeCodec::AAC,
            'hash' => fake()->md5(),
            'location' => fake()->filePath() . '.mp4',
        ];
    }
}
