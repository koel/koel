<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Support\Collection;

class MultipleSongsLiked extends Event
{
    public function __construct(public Collection $songs, public User $user)
    {
    }
}
