<?php

namespace App\Events;

use App\Models\PlaylistCollaborationToken;
use App\Models\User;
use Illuminate\Queue\SerializesModels;

class NewPlaylistCollaboratorJoined extends Event
{
    use SerializesModels;

    public function __construct(public User $collaborator, public PlaylistCollaborationToken $token)
    {
    }
}
