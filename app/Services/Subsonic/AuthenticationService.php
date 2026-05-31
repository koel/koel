<?php

namespace App\Services\Subsonic;

use App\Exceptions\Subsonic\InvalidCredentialsException;
use App\Exceptions\Subsonic\RequiredParameterMissingException;
use App\Helpers\Uuid;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Container\Attributes\Config;
use SensitiveParameter;

class AuthenticationService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        #[Config('app.key')]
        private readonly string $appKey,
    ) {}

    public function hash(#[SensitiveParameter] string $apiKey): string
    {
        return hash_hmac('sha256', $apiKey, $this->appKey);
    }

    public function assignApiKey(User $user, #[SensitiveParameter] ?string $apiKey = null, bool $save = true): void
    {
        $apiKey ??= self::generateApiKey();
        $user->subsonic_api_key = $apiKey;
        $user->subsonic_api_key_hash = $this->hash($apiKey);

        if ($save) {
            $user->saveQuietly();
        }
    }

    private static function generateApiKey(): string
    {
        return Uuid::generate();
    }

    public function authenticate(
        #[SensitiveParameter]
        string $apiKey,
        string $username,
        #[SensitiveParameter]
        string $token,
        #[SensitiveParameter]
        string $salt,
        #[SensitiveParameter]
        string $password,
    ): User {
        $user =
            $this->authenticateViaApiKey($apiKey) ?? $this->authenticateViaToken(
                $username,
                $token,
                $salt,
            ) ?? $this->authenticateViaPassword($username, $password);

        throw_unless($user, RequiredParameterMissingException::class);

        return $user;
    }

    public function authenticateViaApiKey(#[SensitiveParameter] string $apiKey): ?User
    {
        if ($apiKey === '') {
            return null;
        }

        $user = $this->userRepository->findOneBySubsonicApiKeyHash($this->hash($apiKey));
        throw_unless($user, InvalidCredentialsException::class);

        return $user;
    }

    public function authenticateViaToken(
        string $username,
        #[SensitiveParameter]
        string $token,
        #[SensitiveParameter]
        string $salt,
    ): ?User {
        if ($username === '' || $token === '') {
            return null;
        }

        throw_if($salt === '', RequiredParameterMissingException::class);

        $user = $this->userRepository->findOneByEmail($username);
        throw_unless($user, InvalidCredentialsException::class);

        $expected = md5($user->subsonic_api_key . $salt);
        throw_unless(hash_equals($expected, strtolower($token)), InvalidCredentialsException::class);

        return $user;
    }

    public function authenticateViaPassword(string $username, #[SensitiveParameter] string $password): ?User
    {
        if ($username === '' || $password === '') {
            return null;
        }

        $candidate = $password;

        if (str_starts_with($candidate, 'enc:')) {
            $hex = substr($candidate, 4);
            throw_if(
                $hex === '' || (strlen($hex) % 2) !== 0 || !ctype_xdigit($hex),
                InvalidCredentialsException::class,
            );

            $candidate = hex2bin($hex);
        }

        $user = $this->userRepository->findOneByEmail($username);
        throw_unless($user, InvalidCredentialsException::class);
        throw_unless(hash_equals($user->subsonic_api_key, $candidate), InvalidCredentialsException::class);

        return $user;
    }
}
