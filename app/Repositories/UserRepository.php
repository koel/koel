<?php

/** @noinspection PhpIncompatibleReturnTypeInspection */

namespace App\Repositories;

use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;

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

    public function findOneBySocialiteUser(SocialiteUser $socialiteUser, string $provider): ?User
    {
        // we prioritize the SSO ID over the email address, but still resort to the latter
        return User::query()->firstWhere([
            'sso_id' => $socialiteUser->getId(),
            'sso_provider' => $provider,
        ]) ?? $this->findOneByEmail($socialiteUser->getEmail());
    }
}
