<?php

namespace App\Services\Subsonic\Authenticators;

use App\Exceptions\Subsonic\InvalidCredentialsException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Subsonic\Contracts\Authenticator;
use App\Values\Subsonic\SubsonicCredentials;
use Illuminate\Container\Attributes\Config;
use SensitiveParameter;

final class ApiKeyAuthenticator implements Authenticator
{
    public function __construct(
        private readonly UserRepository $userRepository,
        #[Config('app.key')]
        private readonly string $appKey,
    ) {}

    public function attempt(SubsonicCredentials $credentials): ?User
    {
        if (!$credentials->apiKey) {
            return null;
        }

        $user = $this->userRepository->findOneBySubsonicApiKeyHash($this->hash($credentials->apiKey));

        throw_unless($user, InvalidCredentialsException::class);

        return $user;
    }

    public function hash(#[SensitiveParameter] string $apiKey): string
    {
        return hash_hmac('sha256', $apiKey, $this->appKey);
    }
}
