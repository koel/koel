<?php

namespace Database\Factories;

use App\Models\Podcast;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PodcastFactory extends Factory
{
    protected $model = Podcast::class;

    /** @inheritdoc */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'image' => $this->faker->imageUrl(),
            'link' => $this->faker->url,
            'url' => $this->faker->url,
            'author' => $this->faker->name,
            'categories' => [
                ['text' => 'Technology', 'sub_category' => null],
            ],
            'explicit' => $this->faker->boolean,
            'language' => $this->faker->languageCode,
            'metadata' => [
                'locked' => $this->faker->boolean,
                'guid' => Str::uuid()->toString(),
                'owner' => $this->faker->name,
                'copyright' => $this->faker->sentence,
                'txts' => [],
                'fundings' => [],
                'type' => 'episodic',
                'complete' => $this->faker->boolean,
            ],
            'added_by' => User::factory(),
            'last_synced_at' => now(),
        ];
    }
}
