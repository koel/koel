<?php

namespace App\Services;

use App\Exceptions\UserProspectUpdateDeniedException;
use App\Models\Organization;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Values\ImageWritingConfig;
use App\Values\User\SsoUser;
use App\Values\User\UserCreateData;
use App\Values\User\UserUpdateData;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class UserService
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly ImageStorage $imageStorage,
        private readonly OrganizationService $organizationService,
    ) {
    }

    public function createUser(UserCreateData $dto, ?Organization $organization = null): User
    {
        $dto->role->assertAvailable();

        $organization ??= $this->organizationService->getCurrentOrganization();
        $data = $dto->toArray();
        $data['avatar'] = $dto->avatar ? $this->maybeStoreAvatar($dto->avatar) : null;

        return $organization->users()
            ->create($data)
            ->syncRoles($dto->role);
    }

    public function createOrUpdateUserFromSso(SsoUser $ssoUser): User
    {
        $existingUser = $this->repository->findOneBySso($ssoUser);

        if ($existingUser) {
            $existingUser->update([
                'avatar' => $existingUser->has_custom_avatar ? $existingUser->avatar : $ssoUser->avatar,
                'sso_id' => $ssoUser->id,
                'sso_provider' => $ssoUser->provider,
            ]);

            return $existingUser;
        }

        return $this->createUser(UserCreateData::fromSsoUser($ssoUser));
    }

    public function updateUser(User $user, UserUpdateData $dto): User
    {
        throw_if($user->is_prospect, new UserProspectUpdateDeniedException());
        $dto->role?->assertAvailable();

        $data = [
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $dto->password ?: $user->password,
            'avatar' => $dto->avatar ? $this->maybeStoreAvatar($dto->avatar) : null,
        ];

        if ($user->sso_provider) {
            // SSO users cannot change their password or email
            Arr::forget($data, ['password', 'email']);
        }

        $user->update($data);

        if ($dto->role && $user->role !== $dto->role) {
            $user->syncRoles($dto->role);
        }

        return $user->refresh(); // make sure the roles and permissions are refreshed
    }

    /**
     * @param string $avatar Either the URL of the avatar or image data
     */
    private function maybeStoreAvatar(string $avatar): string
    {
        if (Str::startsWith($avatar, ['http://', 'https://'])) {
            return $avatar;
        }

        return basename($this->imageStorage->storeImage($avatar, ImageWritingConfig::make(maxWidth: 480)));
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
