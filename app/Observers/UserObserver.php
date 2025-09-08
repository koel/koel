<?php

namespace App\Observers;

use App\Helpers\Uuid;
use App\Models\User;
use Illuminate\Support\Facades\File;

class UserObserver
{
    public function creating(User $user): void
    {
        $user->public_id ??= Uuid::generate();
    }

    public function updating(User $user): void
    {
        if (!$user->isDirty('avatar')) {
            return;
        }

        $oldAvatar = $user->getRawOriginal('avatar');

        // If the avatar is being updated, delete the old avatar
        rescue_if($oldAvatar, static fn () => File::delete(image_storage_path($oldAvatar)));
    }

    public function deleted(User $user): void
    {
        rescue_if(
            $user->has_custom_avatar,
            static fn () => File::delete(image_storage_path($user->getRawOriginal('avatar')))
        );
    }
}
