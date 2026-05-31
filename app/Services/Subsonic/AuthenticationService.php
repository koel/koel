<?php

namespace App\Services\Subsonic;

use App\Exceptions\Subsonic\RequiredParameterMissingException;
use App\Helpers\Uuid;
use App\Models\User;
use App\Services\Subsonic\Authenticators\ApiKeyAuthenticator;
use App\Services\Subsonic\Authenticators\PasswordAuthenticator;
use App\Services\Subsonic\Authenticators\TokenAuthenticator;
use App\Services\Subsonic\Contracts\Authenticator;
use App\Values\Subsonic\SubsonicCredentials;
use SensitiveParameter;

class AuthenticationService
{
    public function __construct(
        private readonly ApiKeyAuthenticator $apiKeyAuthenticator,
        private readonly TokenAuthenticator $tokenAuthenticator,
        private readonly PasswordAuthenticator $passwordAuthenticator,
    ) {}

    public function authenticate(SubsonicCredentials $credentials): User
    {
        /** @var array<Authenticator> $authenticators */
        $authenticators = [
            $this->apiKeyAuthenticator,
            $this->tokenAuthenticator,
            $this->passwordAuthenticator,
        ];

        foreach ($authenticators as $authenticator) {
            $user = $authenticator->attempt($credentials);

            if ($user) {
                return $user;
            }
        }

        throw new RequiredParameterMissingException();
    }

    public function assignApiKey(User $user, #[SensitiveParameter] ?string $apiKey = null, bool $save = true): void
    {
        $apiKey ??= self::generateApiKey();
        $user->subsonic_api_key = $apiKey;
        $user->subsonic_api_key_hash = $this->apiKeyAuthenticator->hash($apiKey);

        if ($save) {
            $user->saveQuietly();
        }
    }

    private static function generateApiKey(): string
    {
        return Uuid::generate();
    }
}
