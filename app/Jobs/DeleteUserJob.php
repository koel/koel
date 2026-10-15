<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\UserService;

class DeleteUserJob extends QueuedJob
{
    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public readonly User $user,
    ) {}

    public function handle(UserService $userService): void
    {
        $userService->deleteUser($this->user);
    }
}
