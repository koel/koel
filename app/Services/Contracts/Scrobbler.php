<?php

namespace App\Services\Contracts;

use App\Models\Song;
use App\Models\User;

interface Scrobbler
{
    /**
     * Determine whether the user has connected their account to this service.
     */
    public function isConnected(User $user): bool;

    public function scrobble(Song $song, User $user, int $timestamp): void;

    public function updateNowPlaying(Song $song, User $user): void;
}
