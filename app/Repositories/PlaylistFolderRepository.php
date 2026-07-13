<?php

namespace App\Repositories;

use App\Models\PlaylistFolder;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Repository<PlaylistFolder>
 */
class PlaylistFolderRepository extends Repository
{
    /** @return Collection<int, PlaylistFolder> */
    public function getAllOwnedByForUpdate(User $user): Collection
    {
        return PlaylistFolder::query()->where('user_id', $user->id)->orderBy('id')->lockForUpdate()->get();
    }

    public function findOneForUpdate(string $id): ?PlaylistFolder
    {
        return PlaylistFolder::query()->whereKey($id)->lockForUpdate()->first();
    }
}
