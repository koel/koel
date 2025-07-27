<?php

namespace App\Events;

use App\Models\Song;
use App\Models\User;

class SongFavoriteToggled extends Event
{
    public function __construct(public readonly Song $song, public bool $favorite, public readonly User $user)
    {
    }
}
