<?php

namespace App\Values\Subsonic;

use SensitiveParameter;

final class SubsonicCredentials
{
    public function __construct(
        #[SensitiveParameter]
        public readonly string $apiKey,
        public readonly string $username,
        #[SensitiveParameter]
        public readonly string $token,
        #[SensitiveParameter]
        public readonly string $salt,
        #[SensitiveParameter]
        public readonly string $password,
    ) {}
}
