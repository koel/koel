<?php

namespace Database\Factories;

use App\Enums\FavoriteableType;
use App\Models\Favorite;
use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Favorite> */
class FavoriteFactory extends Factory
{
    /** @inheritdoc */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'favoriteable_type' => FavoriteableType::PLAYABLE->value,
            'favoriteable_id' => Song::factory(),
        ];
    }
}
