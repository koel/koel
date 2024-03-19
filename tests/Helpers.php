<?php

namespace Tests;

use App\Models\User;

function create_user(array $attributes = []): User
{
    return User::factory()->create($attributes);
}

function create_admin(array $attributes = []): User
{
    return User::factory()->admin()->create($attributes);
}

function test_path(string $path = ''): string
{
    return base_path('tests' . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR));
}

function read_as_data_url(string $path): string
{
    return 'data:' . mime_content_type($path) . ';base64,' . base64_encode(file_get_contents($path));
}
