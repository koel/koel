<?php

namespace Database\Factories;

use App\Models\Album;
use App\Models\Podcast;
use Illuminate\Database\Eloquent\Factories\Factory;

class SongFactory extends Factory
{
    /** @return array<mixed> */
    public function definition(): array
    {
        return [
            'album_id' => Album::factory(),
            'artist_id' => static fn (array $attributes) => Album::query()->find($attributes['album_id'])->artist_id, // @phpstan-ignore-line
            'title' => $this->faker->sentence,
            'length' => $this->faker->randomFloat(2, 10, 500),
            'track' => random_int(1, 20),
            'disc' => random_int(1, 5),
            'lyrics' => $this->faker->paragraph(),
            'path' => '/tmp/' . uniqid('', true) . '.mp3',
            'genre' => $this->faker->randomElement(['Rock', 'Pop', 'Jazz', 'Classical', 'Metal', 'Hip Hop', 'Rap']),
            'year' => $this->faker->year(),
            'is_public' => true,
            'owner_id' => static fn (array $attributes) => Album::query()->find($attributes['album_id'])->user_id, // @phpstan-ignore-line
            'mtime' => time(),
            'mime_type' => 'audio/mpeg',
        ];
    }

    public function public(): self
    {
        return $this->state(fn () => ['is_public' => true]); // @phpcs:ignore
    }

    public function private(): self
    {
        return $this->state(fn () => ['is_public' => false]); // @phpcs:ignore
    }

    public function asEpisode(): self
    {
        return $this->state(fn () => [ // @phpcs:ignore
            'podcast_id' => Podcast::factory(),
            'is_public' => true,
            'artist_id' => null,
            'owner_id' => null,
            'album_id' => null,
            'storage' => null,
            'path' => $this->faker->url(),
            'lyrics' => '',
            'track' => null,
            'disc' => 0,
            'year' => null,
            'mime_type' => null,
        ]);
    }
}
