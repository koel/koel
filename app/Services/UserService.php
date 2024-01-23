<?php

namespace App\Services;

use App\Exceptions\UserProspectUpdateDeniedException;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;

class UserService
{
    public function __construct(private Hasher $hash)
    {
    }

    public function createUser(string $name, string $email, string $plainTextPassword, bool $isAdmin): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $this->hash->make($plainTextPassword),
            'is_admin' => $isAdmin,
        ]);
    }

    public function updateUser(User $user, string $name, string $email, string|null $password, bool $isAdmin): User
    {
        throw_if($user->is_prospect, new UserProspectUpdateDeniedException());

        $data = [
            'name' => $name,
            'email' => $email,
            'is_admin' => $isAdmin,
        ];

        if ($password) {
            $data['password'] = $this->hash->make($password);
        }

        $user->update($data);

        return $user;
    }

    public function deleteUser(User $user): void
    {
        $user->delete();
    }

    public function savePreference(User $user, string $key, mixed $value): void
    {
        $user->preferences = $user->preferences->set($key, $value);

        $user->save();
    }
}
