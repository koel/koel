<?php

namespace App\Observers;

use App\Helpers\Uuid;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

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

        // Only delete the old avatar if it's a locally stored file (not a URL)
        if ($oldAvatar && !Str::startsWith($oldAvatar, ['http://', 'https://'])) {
            rescue_if($oldAvatar, static fn () => File::delete(image_storage_path($oldAvatar)));
        }
    }

    public function deleted(User $user): void
    {
        rescue_if(
            $user->has_custom_avatar,
            static fn () => File::delete(image_storage_path($user->getRawOriginal('avatar')))
        );
    }
}
