<?php

namespace Database\Factories;

use App\Models\User;
use App\Values\SmartPlaylistRule;
use App\Values\SmartPlaylistRuleGroup;
use App\Values\SmartPlaylistRuleGroupCollection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PlaylistFactory extends Factory
{
    /** @return array<mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
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
