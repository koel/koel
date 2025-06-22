<?php

namespace App\Events;

use App\Models\PlaylistCollaborationToken;
use App\Models\User;

class NewPlaylistCollaboratorJoined extends Event
{
    public function __construct(public User $collaborator, public PlaylistCollaborationToken $token)
    {
    }
}
