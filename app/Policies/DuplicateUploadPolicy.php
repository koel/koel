<?php

namespace App\Policies;

use App\Models\DuplicateUpload;
use App\Models\User;

class DuplicateUploadPolicy
{
    public function own(User $user, DuplicateUpload $upload): bool
    {
        return $upload->user_id === $user->id;
    }
}
