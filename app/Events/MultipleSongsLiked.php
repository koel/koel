<?php

namespace App\Events;

use App\Models\Song;
use App\Models\User;
use Illuminate\Support\Collection;

class MultipleSongsLiked extends Event
{
    /**
     * @param Collection<Song> $songs
     */
    public function __construct(public readonly Collection $songs, public readonly User $user)
    {
    }
}
