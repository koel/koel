<?php

namespace App\Observers;

use App\Helpers\Uuid;
use App\Models\User;
use App\Services\Image\ModelImageObserver;

class UserObserver
{
    private ModelImageObserver $avatarObserver;

    public function __construct()
    {
        $this->avatarObserver = ModelImageObserver::make('avatar');
    }

    public function creating(User $user): void
    {
        $user->public_id ??= Uuid::generate();
    }

    public function updating(User $user): void
    {
        $this->avatarObserver->onModelUpdating($user);
    }

    public function deleted(User $user): void
    {
        $this->avatarObserver->onModelDeleted($user);
    }
}
