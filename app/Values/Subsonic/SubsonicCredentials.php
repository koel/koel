<?php

namespace App\Values\Subsonic;

use App\Exceptions\Subsonic\InvalidCredentialsException;
use Illuminate\Support\Str;
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

    public function decodedPassword(): string
    {
        return Str::startsWith($this->password, 'enc:')
            ? self::decodeHex(Str::substr($this->password, 4))
            : $this->password;
    }

    private static function decodeHex(#[SensitiveParameter] string $hex): string
    {
        throw_if(!$hex || (Str::length($hex) % 2) !== 0 || !ctype_xdigit($hex), InvalidCredentialsException::class);

        return hex2bin($hex);
    }
}
