<?php

namespace App\Services;

use App\Exceptions\UserProspectUpdateDeniedException;
use App\Facades\License;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Values\SSOUser;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UserService
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly Hasher $hash,
        private readonly ImageWriter $imageWriter
    ) {
    }

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    public function createUser(
        string $name,
        string $email,
        string $plainTextPassword,
        bool $isAdmin,
        ?string $avatar = null,
        ?string $ssoId = null,
        ?string $ssoProvider = null,
    ): User {
        if ($ssoProvider) {
            License::requirePlus();
            SSOUser::assertValidProvider($ssoProvider);
        }

        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $plainTextPassword ? $this->hash->make($plainTextPassword) : '',
            'is_admin' => $isAdmin,
            'sso_id' => $ssoId,
            'sso_provider' => $ssoProvider,
            'avatar' =>  $avatar ? $this->createNewAvatar($avatar) : null,
        ]);
    }

    public function createOrUpdateUserFromSSO(SSOUser $ssoUser): User
    {
        License::requirePlus();

        $existingUser = $this->repository->findOneBySSO($ssoUser);

        if ($existingUser) {
            $existingUser->update([
                'avatar' => $existingUser->has_custom_avatar ? $existingUser->avatar : $ssoUser->avatar,
                'sso_id' => $ssoUser->id,
                'sso_provider' => $ssoUser->provider,
            ]);

            return $existingUser;
        }

        return $this->createUser(
            name: $ssoUser->name,
            email: $ssoUser->email,
            plainTextPassword: '',
            isAdmin: false,
            avatar: $ssoUser->avatar,
            ssoId: $ssoUser->id,
            ssoProvider: $ssoUser->provider,
        );
    }

    public function updateUser(
        User $user,
        string $name,
        string $email,
        ?string $password = null,
        ?bool $isAdmin = null,
        ?string $avatar = null
    ): User {
        throw_if($user->is_prospect, new UserProspectUpdateDeniedException());

        if ($user->sso_provider) {
            // An SSO profile is largely managed by the SSO provider
            $user->update([
                'is_admin' => $isAdmin ?? $user->is_admin,
                'name' => $name,
                'avatar' => $avatar ? $this->createNewAvatar($avatar, $user) : null,
            ]);
        } else {
            $user->update([
                'name' => $name,
                'email' => $email,
                'password' => $password ? $this->hash->make($password) : $user->password,
                'is_admin' => $isAdmin ?? $user->is_admin,
                'avatar' => $avatar ? $this->createNewAvatar($avatar, $user) : null,
            ]);
        }

        return $user;
    }

    /**
     * @param string $avatar Either the URL of the avatar or image data
     */
    private function createNewAvatar(string $avatar, ?User $user = null): string
    {
        if (Str::startsWith($avatar, ['http://', 'https://'])) {
            return $avatar;
        }

        $path = self::generateUserAvatarPath();
        $this->imageWriter->write($path, $avatar, ['max_width' => 480]);

        optional($user?->getRawOriginal('avatar'), static fn (string $oldAvatar) => File::delete($oldAvatar));

        return basename($path);
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
