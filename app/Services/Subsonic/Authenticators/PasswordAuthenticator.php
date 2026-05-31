<?php

namespace App\Services\Subsonic\Authenticators;

use App\Exceptions\Subsonic\InvalidCredentialsException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Subsonic\Contracts\Authenticator;
use App\Values\Subsonic\SubsonicCredentials;
use Illuminate\Support\Str;
use SensitiveParameter;

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

        $candidate = Str::startsWith($credentials->password, 'enc:')
            ? self::decodeHexPassword(Str::substr($credentials->password, 4))
            : $credentials->password;

        $user = $this->userRepository->findOneByEmail($credentials->username);

        throw_unless(hash_equals($user->subsonic_api_key ?? '', $candidate), InvalidCredentialsException::class);

        return $user;
    }

    private static function decodeHexPassword(#[SensitiveParameter] string $hex): string
    {
        throw_if(!$hex || (Str::length($hex) % 2) !== 0 || !ctype_xdigit($hex), InvalidCredentialsException::class);

        return hex2bin($hex);
    }
}
