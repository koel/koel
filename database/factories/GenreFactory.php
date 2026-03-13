<?php

namespace Database\Factories;

use App\Models\Genre;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Genre> */
class GenreFactory extends Factory
{
    protected $model = Genre::class;

    /** @inheritdoc */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->name(),
        ];
    }
}
