<?php

namespace App\Services;

use App\Enums\Acl\Role;
use App\Exceptions\UserProspectUpdateDeniedException;
use App\Facades\Dispatcher;
use App\Jobs\DeleteUserJob;
use App\Models\Organization;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Auth\TokenManager;
use App\Services\Image\ImageStorage;
use App\Values\ImageWritingConfig;
use App\Values\User\SsoUser;
use App\Values\User\UserCreateData;
use App\Values\User\UserPreferences;
use App\Values\User\UserUpdateData;
use Closure;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SensitiveParameter;

// @mago-ignore lint:cyclomatic-complexity
class UserService
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly ImageStorage $imageStorage,
        private readonly OrganizationService $organizationService,
        private readonly EmailChangeService $emailChangeService,
        private readonly SecurityNoticeService $securityNoticeService,
        private readonly TokenManager $tokenManager,
        #[Config('koel.sso.default_role')]
        private readonly Role $defaultSsoRole = Role::USER,
    ) {}

    public function createUser(UserCreateData $dto, ?Organization $organization = null): User
    {
        $dto->role->assertAvailable();

        $organization ??= $this->organizationService->getCurrentOrganization();
        $data = $dto->toArray();
        $data['avatar'] = $dto->avatar ? $this->maybeStoreAvatar($dto->avatar) : null;

        /** @var User $user */
        $user = $organization->users()->create($data);

        $organization->claimOwnership($user);

        return $user->syncRoles($dto->role);
    }

    public function createOrUpdateUserFromSso(SsoUser $ssoUser): User
    {
        $existingUser = $this->repository->findOneBySso($ssoUser);

        if ($existingUser) {
            if ($existingUser->is_prospect) {
                $existingUser->fill([
                    'name' => $ssoUser->name,
                    'invitation_token' => null,
                    'invitation_accepted_at' => now(),
                ]);
            }

            $existingUser->update([
                'avatar' => $existingUser->has_custom_avatar
                    ? $existingUser->getRawOriginal('avatar')
                    : $ssoUser->avatar,
                'sso_id' => $ssoUser->id,
                'sso_provider' => $ssoUser->provider,
            ]);

            return $existingUser;
        }

        return $this->createUser(UserCreateData::fromSsoUser($ssoUser, $this->defaultSsoRole));
    }

    public function changePassword(User $user, #[SensitiveParameter] string $newPassword): void
    {
        $user->password = $newPassword;
        $user->save();

        $this->securityNoticeService->notifyPasswordChanged($user);
    }

    public function updateUser(User $user, UserUpdateData $dto): User
    {
        $previousEmail = $user->email;

        $this->applyUpdate($user, $dto, keepCurrentEmail: false);

        if ($user->wasChanged('password')) {
            $this->tokenManager->destroyTokens($user);
        }

        if ($user->email !== $previousEmail) {
            $this->emailChangeService->notifyChange($user, $previousEmail);
        }

        return $user->refresh(); // make sure the roles and permissions are refreshed
    }

    public function updateOwnProfile(User $user, UserUpdateData $dto): User
    {
        $emailChangeRequiresConfirmation = $this->emailChangeService->requiresConfirmation($user, $dto->email);

        $this->applyUpdate($user, $dto, keepCurrentEmail: $emailChangeRequiresConfirmation);

        if ($emailChangeRequiresConfirmation) {
            $this->emailChangeService->requestChange($user, $dto->email);
        }

        return $user->refresh(); // make sure the roles and permissions are refreshed
    }

    private function applyUpdate(User $user, UserUpdateData $dto, bool $keepCurrentEmail): void
    {
        throw_if($user->is_prospect, new UserProspectUpdateDeniedException());
        $dto->role?->assertAvailable();

        $data = [
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $dto->password ?? $user->password,
        ];

        if ($dto->avatar) {
            $data['avatar'] = $dto->avatar->image ? $this->maybeStoreAvatar($dto->avatar->image) : null;
        }

        if ($user->sso_provider) {
            // SSO users cannot change their password or email
            Arr::forget($data, ['password', 'email']);
        }

        if ($keepCurrentEmail) {
            Arr::forget($data, 'email');
        }

        $user->update($data);

        if ($user->wasChanged('password')) {
            $this->securityNoticeService->notifyPasswordChanged($user);
        }

        if ($dto->role && $user->role !== $dto->role) {
            $user->syncRoles($dto->role);
        }
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

    public function scheduleUserDeletion(User $user): void
    {
        $user->deletion_requested_at = now();
        $user->save();

        $this->tokenManager->destroyTokens($user);

        Dispatcher::dispatch(new DeleteUserJob($user));
    }

    public function deleteUser(User $user): void
    {
        $user->delete();
    }

    public function savePreference(User $user, string $key, mixed $value): void
    {
        $this->updatePreferences($user, static fn (UserPreferences $preferences): UserPreferences => $preferences->set(
            $key,
            $value,
        ));
    }

    /** @param Closure(UserPreferences): UserPreferences $changePreferences */
    public function updatePreferences(User $user, Closure $changePreferences): void
    {
        $user->preferences = DB::transaction(function () use ($user, $changePreferences): UserPreferences {
            $lockedUser = $this->repository->getOneLockedForUpdate($user->id);
            $lockedUser->preferences = $changePreferences($lockedUser->preferences);
            $lockedUser->save();

            return $lockedUser->preferences;
        }, attempts: 3);

        $user->syncOriginalAttribute('preferences');
    }
}
