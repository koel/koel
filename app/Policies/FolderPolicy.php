<?php

namespace App\Policies;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FolderPolicy
{
    public function browse(User $user, Folder $folder): Response
    {
        // We simply deny access if the folder is an upload folder and the user is not the uploader.
        return $folder->browsableBy($user)
            ? Response::allow()
            : Response::deny('You do not have permission to browse this folder.');
    }
}
