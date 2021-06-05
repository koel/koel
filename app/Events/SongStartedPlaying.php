<?php

namespace App\Events;

use App\Models\Song;
use App\Models\User;
use Illuminate\Queue\SerializesModels;

class SongStartedPlaying extends Event
{
    use SerializesModels;

    public Song $song;
    public User $user;

    public function __construct(Song $song, User $user)
    {
        $this->song = $song;
        $this->user = $user;
    }
}
