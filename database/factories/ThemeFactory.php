<?php

namespace Database\Factories;

use App\Models\Theme;
use App\Models\User;
use App\Values\Theme\ThemeProperties;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Theme> */
class ThemeFactory extends Factory
{
    /** @inheritdoc */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'properties' => ThemeProperties::make(
                fgColor: fake()->hexColor(),
                bgColor: fake()->hexColor(),
                bgImage: '',
                highlightColor: fake()->hexColor(),
                fontFamily: fake()->word(),
                fontSize: fake()->numberBetween(13, 20),
            ),
            'user_id' => User::factory(),
        ];
    }
}
