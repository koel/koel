<?php

namespace App\Values\Subsonic;

use SensitiveParameter;

final readonly class SubsonicCredentials
{
    private function __construct(
        #[SensitiveParameter]
        public string $apiKey,
        public string $username,
        #[SensitiveParameter]
        public string $token,
        #[SensitiveParameter]
        public string $salt,
        #[SensitiveParameter]
        public string $password,
    ) {}

    public static function make(
        #[SensitiveParameter]
        string $apiKey = '',
        string $username = '',
        #[SensitiveParameter]
        string $token = '',
        #[SensitiveParameter]
        string $salt = '',
        #[SensitiveParameter]
        string $password = '',
    ): self {
        return new self(apiKey: $apiKey, username: $username, token: $token, salt: $salt, password: $password);
    }
}
