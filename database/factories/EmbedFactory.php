<?php

namespace Database\Factories;

use App\Enums\EmbeddableType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmbedFactory extends Factory
{
    /** @inheritdoc */
    public function definition(): array
    {
        /** @var EmbeddableType $type */
        $type = $this->faker->randomElement(EmbeddableType::cases());

        return [
            'user_id' => User::factory(),
            'embeddable_type' => $type->value,
            'embeddable_id' => $type->modelClass()::factory(),
        ];
    }
}
