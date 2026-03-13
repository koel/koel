<?php

namespace Database\Factories;

use App\Models\Folder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Folder> */
class FolderFactory extends Factory
{
    private static function generateRandomPath(): string
    {
        return implode(DIRECTORY_SEPARATOR, [
            bin2hex(random_bytes(5)),
            Str::ulid(),
        ]);
    }

    /** @inheritdoc  */
    public function definition(): array
    {
        return [
            'path' => self::generateRandomPath(),
        ];
    }
}
