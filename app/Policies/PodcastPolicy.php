<?php

namespace App\Policies;

use App\Models\Podcast;
use App\Models\User;

class PodcastPolicy
{
    public function view(User $user, Podcast $podcast): bool
    {
        return $user->subscribedToPodcast($podcast);
    }
}
