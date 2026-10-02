<?php

namespace Database\Factories;

use App\Models\Song;
use App\Models\SongAnalysis;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SongAnalysis> */
class SongAnalysisFactory extends Factory
{
    /** @inheritdoc */
    public function definition(): array
    {
        return [
            'song_id' => Song::factory(),
            'loudness' => fake()->randomFloat(1, -20, -6),
            'true_peak' => fake()->randomFloat(1, -3, 2),
            'levels' => array_map(static fn (): float => fake()->randomFloat(3, 0, 1), range(1, 800)),
        ];
    }
}
