<?php

namespace Tests\Unit\Socialite\OpenIDConnect;

use App\Socialite\OpenIDConnect\Provider;
use Laravel\Socialite\Two\User;

class TestableProvider extends Provider
{
    /** @param array<string, mixed> $user */
    public function mapUserPublic(array $user): User
    {
        return $this->mapUserToObject($user);
    }
}
