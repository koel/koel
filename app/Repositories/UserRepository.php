<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository extends Repository
{
    public function getDefaultAdminUser(): User
    {
        return User::query()->where('is_admin', true)->oldest()->firstOrFail();
    }

    public function findOneByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }
}
