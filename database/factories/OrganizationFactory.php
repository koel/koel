<?php

namespace Database\Factories;

use App\Helpers\Ulid;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * @inheritdoc
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'slug' => Ulid::generate(),
        ];
    }
}
