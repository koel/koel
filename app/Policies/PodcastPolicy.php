<?php

namespace App\Policies;

use App\Models\Podcast;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PodcastPolicy
{
    public function access(User $user, Podcast $podcast): Response
    {
        return $user->subscribedToPodcast($podcast)
            ? Response::allow()
            : Response::deny();
    }

    public function view(User $user, Podcast $podcast): Response
    {
        return $user->subscribedToPodcast($podcast)
            ? Response::allow()
            : Response::deny();
    }
}
