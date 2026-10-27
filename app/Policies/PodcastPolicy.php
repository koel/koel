<?php

namespace App\Policies;

use App\Enums\Acl\Role;
use App\Models\Podcast;
use App\Models\User;

class PodcastPolicy
{
    public function access(User $user, Podcast $podcast): bool
    {
        return $user->subscribedToPodcast($podcast);
    }

    public function create(User $user): bool
    {
        return $user->role !== Role::GUEST;
    }

    public function view(User $user, Podcast $podcast): bool
    {
        return $user->subscribedToPodcast($podcast);
    }
}
