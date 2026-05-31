<?php

namespace App\Services\Subsonic\Authenticators;

use App\Exceptions\Subsonic\InvalidCredentialsException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Subsonic\Contracts\Authenticator;
use App\Values\Subsonic\SubsonicCredentials;
use Illuminate\Support\Str;

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

        $candidate = $credentials->password;

        if (Str::startsWith($candidate, 'enc:')) {
            $hex = Str::substr($candidate, 4);
            throw_if(!$hex || (Str::length($hex) % 2) !== 0 || !ctype_xdigit($hex), InvalidCredentialsException::class);

            $candidate = hex2bin($hex);
        }

        $user = $this->userRepository->findOneByEmail($credentials->username);
        throw_unless(hash_equals($user->subsonic_api_key ?? '', $candidate), InvalidCredentialsException::class);

        return $user;
    }
}
