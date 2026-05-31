<?php

namespace App\Services\Subsonic\Authenticators;

use App\Exceptions\Subsonic\InvalidCredentialsException;
use App\Exceptions\Subsonic\RequiredParameterMissingException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Subsonic\Contracts\Authenticator;
use App\Values\Subsonic\SubsonicCredentials;
use Illuminate\Support\Str;

final class TokenAuthenticator implements Authenticator
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function attempt(SubsonicCredentials $credentials): ?User
    {
        if (!$credentials->username || !$credentials->token) {
            return null;
        }

        throw_unless($credentials->salt, RequiredParameterMissingException::class);

        $user = $this->userRepository->findOneByEmail($credentials->username);

        throw_unless(
            $user
            && $user->subsonic_api_key
            && hash_equals(md5($user->subsonic_api_key . $credentials->salt), Str::lower($credentials->token)),
            InvalidCredentialsException::class,
        );

        return $user;
    }
}
