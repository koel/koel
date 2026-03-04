<?php

namespace Database\Factories;

use App\Helpers\Ulid;
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
            'logo' => Ulid::generate() . '.webp',
            'description' => $this->faker->text(),
            'is_public' => false,
        ];
    }
}
