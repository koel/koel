<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    /** @inheritdoc */
    public function definition(): array
    {
        return [
            'key' => $this->faker->slug,
            'value' => $this->faker->name,
        ];
    }
}
