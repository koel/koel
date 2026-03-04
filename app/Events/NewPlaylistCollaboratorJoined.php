<?php

namespace App\Events;

use App\Models\PlaylistCollaborationToken;
use App\Models\User;
use SensitiveParameter;

class NewPlaylistCollaboratorJoined extends Event
{
    public function __construct(
        public User $collaborator,
        #[SensitiveParameter]
        public PlaylistCollaborationToken $token,
    ) {}
}
