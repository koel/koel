<?php

namespace Database\Factories;

use App\Models\Podcast;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Podcast> */
class PodcastFactory extends Factory
{
    /** @inheritdoc */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence,
            'description' => fake()->paragraph,
            'image' => fake()->imageUrl(),
            'link' => fake()->url,
            'url' => fake()->url,
            'author' => fake()->name,
            'categories' => [
                ['text' => 'Technology', 'sub_category' => null],
            ],
            'explicit' => fake()->boolean,
            'language' => fake()->languageCode,
            'metadata' => [
                'locked' => fake()->boolean,
                'guid' => Str::uuid()->toString(),
                'owner' => fake()->name,
                'copyright' => fake()->sentence,
                'txts' => [],
                'fundings' => [],
                'type' => 'episodic',
                'complete' => fake()->boolean,
            ],
            'added_by' => User::factory(),
            'last_synced_at' => now(),
        ];
    }
}
