<?php

/** @noinspection PhpIncompatibleReturnTypeInspection */

namespace App\Repositories;

use App\Models\User;
use App\Values\SSOUser;

/**
 * @extends Repository<User>
 */
class UserRepository extends Repository
{
    public function getFirstAdminUser(): User
    {
        return User::firstAdmin();
    }

    public function findOneByEmail(string $email): ?User
    {
        return User::query()->firstWhere('email', $email);
    }

    public function findOneBySSO(SSOUser $ssoUser): ?User
    {
        // we prioritize the SSO ID over the email address, but still resort to the latter
        return User::query()->firstWhere([
            'sso_id' => $ssoUser->id,
            'sso_provider' => $ssoUser->provider,
        ]) ?? $this->findOneByEmail($ssoUser->email);
    }

    public function getOneByPublicId(string $publicId): User
    {
        return $this->getOneBy(['public_id' => $publicId]);
    }
}
