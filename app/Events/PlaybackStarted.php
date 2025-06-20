<?php

namespace App\Events;

use App\Models\Song;
use App\Models\User;

class PlaybackStarted extends Event
{
    public function __construct(public Song $song, public User $user)
    {
    }
}
