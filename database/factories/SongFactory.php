<?php

namespace Database\Factories;

use App\Models\Album;
use App\Models\Podcast;
use App\Models\Song;
use Illuminate\Database\Eloquent\Factories\Factory;
use PhanAn\Poddle\Values\EpisodeMetadata;

/** @extends Factory<Song> */
class SongFactory extends Factory
{
    /** @inheritdoc */
    public function definition(): array
    {
        return [
            'album_id' => Album::factory(),
            'album_name' => static fn (array $attributes) => Album::query()->find($attributes['album_id'])?->name, // @phpstan-ignore-line
            'artist_id' => static fn (array $attributes) => Album::query()->find($attributes['album_id'])?->artist_id, // @phpstan-ignore-line
            'artist_name' => static fn (array $attributes) => Album::query()->find( // @phpstan-ignore-line
                $attributes['album_id'],
            )?->artist_name,
            'title' => fake()->sentence,
            'length' => fake()->randomFloat(2, 10, 500),
            'track' => random_int(1, 20),
            'disc' => random_int(1, 5),
            'lyrics' => fake()->paragraph(),
            'path' => '/tmp/' . uniqid('', true) . '.mp3',
            'year' => fake()->year(),
            'is_public' => true,
            // @mago-ignore lint:prefer-static-closure
            'owner_id' => fn (array $attributes) => Album::query()->find($attributes['album_id'])->user_id, // @phpstan-ignore-line
            'hash' => fake()->md5(),
            'mtime' => time(),
            'mime_type' => 'audio/mpeg',
            'file_size' => fake()->numberBetween(4_000_000, 10_000_000),
        ];
    }

    public function public(): self
    {
        // @mago-ignore lint:prefer-static-closure
        return $this->state(fn () => ['is_public' => true]);
    }

    public function private(): self
    {
        // @mago-ignore lint:prefer-static-closure
        return $this->state(fn () => ['is_public' => false]);
    }

    public function asEpisode(): self
    {
        // @mago-ignore lint:prefer-static-closure
        return $this->state(fn () => [
            'podcast_id' => Podcast::factory(),
            'episode_metadata' => EpisodeMetadata::fromArray([
                'link' => fake()->url(),
                'description' => fake()->paragraph,
                'duration' => fake()->randomFloat(2, 10, 500),
                'image' => fake()->imageUrl(),
            ]),
            'is_public' => true,
            'artist_id' => null,
            'owner_id' => null,
            'album_id' => null,
            'storage' => null,
            'path' => fake()->url(),
            'lyrics' => '',
            'track' => null,
            'disc' => 0,
            'year' => null,
            'mime_type' => null,
            'file_size' => null,
        ]);
    }
}
