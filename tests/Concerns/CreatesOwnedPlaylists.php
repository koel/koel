<?php

namespace Tests\Concerns;

use App\Models\Playlist;
use App\Models\User;

trait CreatesOwnedPlaylists
{
    protected static function playlistOwnedBy(User $user, bool $smart = false): Playlist
    {
        $factory = Playlist::factory();
        $playlist = $smart ? $factory->smart()->createOne() : $factory->createOne();
        $playlist->users()->detach();
        $playlist->users()->attach($user, ['role' => 'owner']);

        return $playlist;
    }
}
