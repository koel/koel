<?php

namespace App\Policies;

use App\Models\PlaylistFolder;
use App\Models\User;

class PlaylistFolderPolicy
{
    public function own(User $user, PlaylistFolder $folder): bool
    {
        return $folder->user->is($user);
    }
}
