<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository extends Repository
{
    public function guessModelClass(): string
    {
        return User::class;
    }
}
