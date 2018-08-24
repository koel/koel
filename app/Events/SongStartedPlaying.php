<?php

namespace App\Events;

use App\Models\Song;
use App\Models\User;
use Illuminate\Queue\SerializesModels;

class SongStartedPlaying extends Event
{
    use SerializesModels;

    public $song;
    public $user;

    public function __construct(Song $song, User $user)
    {
        $this->song = $song;
        $this->user = $user;
    }
}
