<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FolderFactory extends Factory
{
    private static function generateRandomPath(): string
    {
        return implode(
            DIRECTORY_SEPARATOR,
            [
                bin2hex(random_bytes(5)),
                Str::ulid(),
            ]
        );
    }

    /** @inheritdoc  */
    public function definition(): array
    {
        return [
            'path' => self::generateRandomPath(),
        ];
    }
}
