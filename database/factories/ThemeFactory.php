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
            'name' => $this->faker->words(3, true),
            'properties' => ThemeProperties::make(
                fgColor: $this->faker->hexColor(),
                bgColor: $this->faker->hexColor(),
                bgImage: '',
                highlightColor: $this->faker->hexColor(),
                fontFamily: $this->faker->word(),
                fontSize: $this->faker->numberBetween(13, 20),
            ),
            'user_id' => User::factory(),
        ];
    }
}
