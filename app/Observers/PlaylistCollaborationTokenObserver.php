<?php

namespace App\Observers;

use App\Helpers\Uuid;
use App\Models\PlaylistCollaborationToken;
use SensitiveParameter;

class PlaylistCollaborationTokenObserver
{
    public function creating(#[SensitiveParameter] PlaylistCollaborationToken $token): void
    {
        $token->token ??= Uuid::generate();
    }
}
