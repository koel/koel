<?php

namespace Database\Factories;

use App\Models\Passkey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use ParagonIE\ConstantTime\Base64UrlSafe;

/** @extends Factory<Passkey> */
class PasskeyFactory extends Factory
{
    /** @inheritdoc */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'credential_id' => Base64UrlSafe::encodeUnpadded(random_bytes(32)),
            'credential' => ['aaguid' => '00000000-0000-0000-0000-000000000000'],
            'user_id' => User::factory(),
        ];
    }
}
