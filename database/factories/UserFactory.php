<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->email,
            'password' => Hash::make('secret'),
            'is_admin' => false,
            'preferences' => [],
            'remember_token' => str_random(10),
        ];
    }

    public function admin()
    {
        return $this->state(function (): array {
            return ['is_admin' => true];
        });
    }
}
