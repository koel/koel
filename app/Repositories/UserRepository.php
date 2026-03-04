<?php

/** @noinspection PhpIncompatibleReturnTypeInspection */

namespace App\Repositories;

use App\Enums\Acl\Role as RoleEnum;
use App\Models\Organization;
use App\Models\User;
use App\Values\User\SsoUser;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Repository<User>
 */
class UserRepository extends Repository
{
    public function getOrCreateFirstAdmin(): User
    {
        $defaultOrganization = Organization::default();

        return User::query()
            ->whereRole(RoleEnum::ADMIN)
            ->where('organization_id', $defaultOrganization->id)
            ->oldest()
            ->firstOr(static function () use ($defaultOrganization): User {
                /** @var User $user */
                $user = User::query()->create([
                    'email' => User::FIRST_ADMIN_EMAIL,
                    'name' => User::FIRST_ADMIN_NAME,
                    'password' => Hash::make(User::FIRST_ADMIN_PASSWORD),
                    'organization_id' => $defaultOrganization->id,
                ]);

                return $user->syncRoles(RoleEnum::ADMIN);
            });
    }

    public function findOneByEmail(string $email): ?User
    {
        return User::query()->firstWhere('email', $email);
    }

    public function findOneBySso(SsoUser $ssoUser): ?User
    {
        // we prioritize the SSO ID over the email address but still resort to the latter
        return (
            User::query()->firstWhere([
                'sso_id' => $ssoUser->id,
                'sso_provider' => $ssoUser->provider,
            ]) ?? $this->findOneByEmail($ssoUser->email)
        );
    }

    public function getOneByPublicId(string $publicId): User
    {
        return $this->getOneBy(['public_id' => $publicId]);
    }
}
