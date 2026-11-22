<?php

namespace App\Services;

use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class HiddenSongService
{
    /** @param Collection<int, Song> $songs */
    public function hideSongs(Collection $songs, User $user): void
    {
        $user->hiddenSongs()->syncWithoutDetaching($songs->modelKeys());
    }

    /** @param Collection<int, Song> $songs */
    public function unhideSongs(Collection $songs, User $user): void
    {
        $user->hiddenSongs()->detach($songs->modelKeys());
    }
}
