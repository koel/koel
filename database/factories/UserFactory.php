<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /** @return array<mixed> */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->email,
            'password' => Hash::make('secret'),
            'is_admin' => false,
            'preferences' => [
                'lastfm_session_key' => Str::random(),
            ],
            'remember_token' => Str::random(10),
            'organization_id' => Organization::default()->id,
        ];
    }

    public function admin(): self
    {
        return $this->state(fn () => ['is_admin' => true]); // @phpcs:ignore
    }

    public function prospect(): self
    {
        return $this->state(fn () => [ // @phpcs:ignore
            'invitation_token' => Str::random(),
            'invited_at' => now(),
            'invited_by_id' => User::factory()->admin(),
        ]);
    }
}
