<?php

namespace App\Repositories;

use App\Models\PlaylistFolder;
use App\Repositories\Traits\ByCurrentUser;

class PlaylistFolderRepository extends Repository
{
    use ByCurrentUser;

    public function guessModelClass(): string
    {
        return PlaylistFolder::class;
    }
}
