<?php

namespace App\Services\Subsonic\Authenticators;

use App\Exceptions\Subsonic\InvalidCredentialsException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Subsonic\Contracts\Authenticator;
use App\Values\Subsonic\SubsonicCredentials;

final class PasswordAuthenticator implements Authenticator
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function attempt(SubsonicCredentials $credentials): ?User
    {
        if (!$credentials->username || !$credentials->password) {
            return null;
        }

        $user = $this->userRepository->findOneByEmail($credentials->username);

        throw_unless(
            $user && $user->subsonic_api_key && hash_equals($user->subsonic_api_key, $credentials->password),
            InvalidCredentialsException::class,
        );

        return $user;
    }
}
