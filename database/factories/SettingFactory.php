<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Setting> */
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
