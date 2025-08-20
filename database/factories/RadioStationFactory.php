<?php

namespace Database\Factories;

use App\Models\RadioStation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RadioStation>
 */
class RadioStationFactory extends Factory
{
    /**
     * @inheritdoc
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->company(),
            'url' => $this->faker->url(),
            'logo' => $this->faker->imageUrl(128, 128),
            'description' => $this->faker->text(),
            'is_public' => false,
        ];
    }
}
