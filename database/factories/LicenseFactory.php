<?php

namespace Database\Factories;

use App\Models\License;
use App\Values\License\LicenseInstance;
use App\Values\License\LicenseMeta;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<License> */
class LicenseFactory extends Factory
{
    /** @inheritdoc */
    public function definition(): array
    {
        return [
            'key' => Str::uuid()->toString(),
            'hash' => Str::random(32),
            'instance' => LicenseInstance::make(id: Str::uuid()->toString(), name: 'Koel Plus', createdAt: now()),
            'meta' => LicenseMeta::make(
                customerId: fake()->numberBetween(1, 1000),
                customerName: fake()->name(),
                customerEmail: fake()->email(),
            ),
            'expires_at' => null,
        ];
    }
}
