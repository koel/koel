<?php

namespace App\Services;

use App\Exceptions\UserProspectUpdateDeniedException;
use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UserService
{
    public function __construct(private Hasher $hash, private ImageWriter $imageWriter)
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

    public function updateUser(
        User $user,
        string $name,
        string $email,
        ?string $password,
        ?bool $isAdmin = null,
        ?string $avatar = null
    ): User {
        throw_if($user->is_prospect, new UserProspectUpdateDeniedException());

        $data = [
            'name' => $name,
            'email' => $email,
        ];

        if ($isAdmin !== null) {
            $data['is_admin'] = $isAdmin;
        }

        if ($password) {
            $data['password'] = $this->hash->make($password);
        }

        if ($avatar) {
            $oldAvatar = $user->getRawOriginal('avatar');

            $path = self::generateUserAvatarPath();
            $this->imageWriter->write($path, $avatar, ['max_width' => 480]);
            $data['avatar'] = basename($path);

            if ($oldAvatar) {
                File::delete($oldAvatar);
            }
        } else {
            $data['avatar'] = null;
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

    private static function generateUserAvatarPath(): string
    {
        return user_avatar_path(sprintf('%s.webp', sha1(Str::uuid())));
    }
}
