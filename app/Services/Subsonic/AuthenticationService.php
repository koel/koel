<?php

namespace App\Services\Subsonic;

use App\Models\User;
use Illuminate\Container\Attributes\Config;
use SensitiveParameter;

class AuthenticationService
{
    public function __construct(
        #[Config('app.key')]
        private readonly string $appKey,
    ) {}

    public function hash(#[SensitiveParameter] string $apiKey): string
    {
        return hash_hmac('sha256', $apiKey, $this->appKey);
    }

    public function assignApiKey(User $user, #[SensitiveParameter] string $apiKey): void
    {
        $user->subsonic_api_key = $apiKey;
        $user->subsonic_api_key_hash = $this->hash($apiKey);
    }
}
