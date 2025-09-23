<?php

namespace Database\Factories;

use App\Helpers\Ulid;
use App\Models\Playlist;
use App\Models\User;
use App\Values\SmartPlaylist\SmartPlaylistRule;
use App\Values\SmartPlaylist\SmartPlaylistRuleGroup;
use App\Values\SmartPlaylist\SmartPlaylistRuleGroupCollection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PlaylistFactory extends Factory
{
    /** @inheritdoc */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'rules' => null,
            'description' => $this->faker->realText(),
            'cover' => Ulid::generate() . '.webp',
        ];
    }

    public function smart(): static
    {
        return $this->state(fn () => [ // @phpcs:ignore
            'rules' => SmartPlaylistRuleGroupCollection::create([
                SmartPlaylistRuleGroup::make([
                    'id' => Str::uuid()->toString(),
                    'rules' => [
                        SmartPlaylistRule::make([
                            'id' => Str::uuid()->toString(),
                            'model' => 'artist.name',
                            'operator' => 'is',
                            'value' => ['foo'],
                        ]),
                    ],
                ]),
            ]),
        ]);
    }

    public function configure(): static
    {
        // @phpstan-ignore-next-line
        return $this->afterCreating(static function (Playlist $playlist): void {
            $playlist->users()->attach(User::factory()->create(), ['role' => 'owner']);
        });
    }
}
