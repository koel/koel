<?php

namespace Tests;

use App\Models\Playlist;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\File;

function create_user(array $attributes = []): User
{
    return User::factory()->create($attributes);
}

function create_admin(array $attributes = []): User
{
    return User::factory()->admin()->create($attributes);
}

function create_user_prospect(array $attributes = []): User
{
    return User::factory()->prospect()->create($attributes);
}

function test_path(string $path = ''): string
{
    return base_path('tests' . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR));
}

function read_as_data_url(string $path): string
{
    return 'data:' . mime_content_type($path) . ';base64,' . base64_encode(File::get($path));
}

/** @param array<mixed> $attributes */
function create_playlist(array $attributes = [], ?User $owner = null, bool $smart = false): Playlist
{
    $owner ??= create_user();

    $playlist = $smart
        ? Playlist::factory()->smart()->create($attributes)
        : Playlist::factory()->create($attributes);

    $owner->playlists()->attach($playlist, ['role' => 'owner']);

    return $playlist;
}

/**
 * @param array<mixed> $attributes
 *
 * @return Collection<Playlist>|array<array-key, Playlist>
 */
function create_playlists(int $count, array $attributes = [], ?User $owner = null): Collection
{
    return Playlist::factory()
        ->count($count)
        ->create($attributes)
        ->each(static fn (Playlist $p) => $p->users()->attach($owner ?? create_user(), ['role' => 'owner']));
}
