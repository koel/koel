<?php

namespace App\Repositories;

use App\Models\DuplicateUpload;
use App\Models\Song;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\File;

class DuplicateUploadRepository extends Repository
{
    public function create(User $user, string $filePath, Song $existingSong): DuplicateUpload
    {
        return DuplicateUpload::query()->create([
            'user_id' => $user->id,
            'file_path' => $filePath,
            'existing_song_id' => $existingSong->id,
        ]);
    }

    public function findForUser(User $user): Collection
    {
        return DuplicateUpload::query()->where('user_id', $user->id)->get();
    }

    public function deleteExpired(int $ttlHours = 24): void
    {
        DuplicateUpload::query()
            ->where('created_at', '<', now()->subHours($ttlHours))
            ->each(static function (DuplicateUpload $record): void {
                File::delete($record->file_path);
                $record->delete();
            });
    }
}
