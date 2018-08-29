<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository extends AbstractRepository
{
    public function getModelClass(): string
    {
        return User::class;
    }
}
