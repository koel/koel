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

function create_manager(array $attributes = []): User
{
    return User::factory()->manager()->create($attributes);
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

function create_playlist(array $attributes = [], bool $smart = false): Playlist
{
    return $smart
        ? Playlist::factory()->smart()->create($attributes)
        : Playlist::factory()->create($attributes);
}

/**
 * @return Collection<Playlist>|array<array-key, Playlist>
 */
function create_playlists(int $count, array $attributes = [], ?User $owner = null): Collection
{
    return Playlist::factory()
        ->count($count)
        ->create($attributes)
        ->when($owner, static function (Collection $playlists) use ($owner): void {
            $playlists->each(static function (Playlist $p) use ($owner): void {
                $p->users()->detach();
                $p->users()->attach($owner, ['role' => 'owner']);
            });
        });
}

/**
 * A minimal base64 encoded image that's still valid binary data and can be used
 * in tests that involve reading/writing image files.
 */
function minimal_base64_encoded_image(): string
{
    return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAgAAAAIAQMAAAD+wSzIAAAABlBMVEX///+/v7+jQ3Y5AAAADklEQVQI12P4AIX8EAgALgAD/aNpbtEAAAAASUVORK5CYII'; // @phpcs:ignore
}
