<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class SongsBatchUnliked
{
    use SerializesModels;

    public Collection $songs;
    public User $user;

    public function __construct(Collection $songs, User $user)
    {
        $this->songs = $songs;
        $this->user = $user;
    }
}
