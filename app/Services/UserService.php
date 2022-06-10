<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;

class UserService
{
    public function __construct(private Hasher $hash)
    {
    }

    public function createUser(string $name, string $email, string $plainTextPassword, bool $isAdmin): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => $this->hash->make($plainTextPassword),
            'is_admin' => $isAdmin,
        ]);
    }

    public function updateUser(User $user, string $name, string $email, string|null $password, bool $isAdmin): User
    {
        $user->update([
            'name' => $name,
            'email' => $email,
            'password' => $password ? $this->hash->make($password) : $user->password,
            'is_admin' => $isAdmin,
        ]);

        return $user;
    }

    public function deleteUser(User $user): void
    {
        $user->delete();
    }
}
