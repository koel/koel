<?php

namespace Database\Factories;

use App\Models\Song;
use App\Models\SongWaveform;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SongWaveform> */
class SongWaveformFactory extends Factory
{
    /** @inheritdoc */
    public function definition(): array
    {
        return [
            'song_id' => Song::factory(),
            'levels' => array_map(static fn (): float => fake()->randomFloat(2, 0, 1), range(1, 800)),
        ];
    }
}
