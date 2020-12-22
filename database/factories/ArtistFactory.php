<?php

namespace Database\Factories;

use App\Models\Artist;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArtistFactory extends Factory
{
    protected $model = Artist::class;

    /** @return array<mixed> */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'image' => md5(uniqid()) . '.jpg',
        ];
    }
}
