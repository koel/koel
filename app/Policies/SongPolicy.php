<?php

namespace App\Policies;

use App\Facades\License;
use App\Models\Song;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SongPolicy
{
    public function access(User $user, Song $song): Response
    {
        return License::isCommunity() || $song->accessibleBy($user)
            ? Response::allow()
            : Response::deny();
    }

    public function own(User $user, Song $song): Response
    {
        return $song->ownedBy($user) ? Response::allow() : Response::deny();
    }

    public function delete(User $user, Song $song): Response
    {
        return (License::isPlus() && $song->ownedBy($user)) || $user->is_admin
            ? Response::allow()
            : Response::deny();
    }

    public function edit(User $user, Song $song): Response
    {
        return (License::isPlus() && $song->accessibleBy($user)) || $user->is_admin
            ? Response::allow()
            : Response::deny();
    }

    public function download(User $user, Song $song): Response
    {
        return $this->access($user, $song);
    }
}
