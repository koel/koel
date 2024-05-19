<?php

namespace App\Policies;

use App\Models\Podcast\Song;
use App\Models\User;

class EpisodePolicy
{
    public function access(User $user, Song $episode): bool
    {
        return $user->subscribedToPodcast($episode->podcast);
    }
}
