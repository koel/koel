<?php

namespace App\Observers;

use App\Helpers\Uuid;
use App\Models\PlaylistCollaborationToken;

class PlaylistCollaborationTokenObserver
{
    public function creating(PlaylistCollaborationToken $token): void
    {
        $token->token ??= Uuid::generate();
    }
}
