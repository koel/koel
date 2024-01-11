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
