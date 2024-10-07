<?php

namespace Database\Factories;

use App\Values\SongUpdateData;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class SongUpdateDataFactory extends Factory
{
    protected $model = SongUpdateData::class;

    /**
     * @return array<string|null|int>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'artistName' => $this->faker->name,
            'albumName' => $this->faker->sentence(2),
            'albumArtistName' => null,
            'track' => $this->faker->numberBetween(1, 15),
            'disc' => $this->faker->numberBetween(1, 2),
            'genre' => $this->faker->word,
            'year' => $this->faker->year,
            'lyrics' => $this->faker->paragraph,
        ];
    }

    /**
     * @param array $attributes
     */
    public function make($attributes = [], ?Model $parent = null): SongUpdateData
    {
        $data = array_merge($this->definition(), $attributes);

        return SongUpdateData::make(
            $data['title'],
            $data['artistName'],
            $data['albumName'],
            $data['albumArtistName'],
            $data['track'],
            $data['disc'],
            $data['genre'],
            $data['year'],
            $data['lyrics']
        );
    }
}
