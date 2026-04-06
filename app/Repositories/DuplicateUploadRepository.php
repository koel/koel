<?php

namespace App\Repositories;

use App\Models\DuplicateUpload;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/** @extends Repository<DuplicateUpload> */
class DuplicateUploadRepository extends Repository
{
    /** @return Collection<DuplicateUpload>|array<array-key, DuplicateUpload> */
    public function getAllForUser(User $user): Collection
    {
        return DuplicateUpload::query()
            ->whereBelongsTo($user)
            ->with('existingSong:id,title,artist_name')
            ->get();
    }

    /** @return Collection<DuplicateUpload>|array<array-key, DuplicateUpload> */
    public function getStaleUploads(int $days): Collection
    {
        return DuplicateUpload::query()->where('created_at', '<', now()->subDays($days))->get();
    }
}
