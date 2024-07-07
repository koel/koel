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
    public function getDefaultAdminUser(): User
    {
        return User::query()->where('is_admin', true)->oldest()->firstOrFail();
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
}
