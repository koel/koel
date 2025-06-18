<?php

namespace Database\Factories;

use App\Values\SmartPlaylist\SmartPlaylistRule;
use App\Values\SmartPlaylist\SmartPlaylistRuleGroup;
use App\Values\SmartPlaylist\SmartPlaylistRuleGroupCollection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PlaylistFactory extends Factory
{
    /** @return array<mixed> */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'rules' => null,
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
}
