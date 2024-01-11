<?php

namespace Database\Factories;

use App\Values\LicenseInstance;
use App\Values\LicenseMeta;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LicenseFactory extends Factory
{
    /** @return array<mixed> */
    public function definition(): array
    {
        return [
            'key' => Str::uuid()->toString(),
            'hash' => Str::random(32),
            'instance' => LicenseInstance::make(
                id: Str::uuid()->toString(),
                name: 'Koel Plus',
                createdAt: now(),
            ),
            'meta' => LicenseMeta::make(
                customerId: $this->faker->numberBetween(1, 1000),
                customerName: $this->faker->name(),
                customerEmail: $this->faker->email()
            ),
            'expires_at' => null,
        ];
    }
}
