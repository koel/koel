<?php

namespace App\Values;

use Illuminate\Http\Request;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Webmozart\Assert\Assert;

final class SSOUser
{
    private function __construct(
        public string $provider,
        public string $id,
        public string $email,
        public string $name,
        public ?string $avatar,
    ) {
        self::assertValidProvider($provider);
    }

    public static function fromSocialite(SocialiteUser $socialiteUser, string $provider): self
    {
        return new self(
            provider: $provider,
            id: $socialiteUser->getId(),
            email: $socialiteUser->getEmail(),
            name: $socialiteUser->getName(),
            avatar: $socialiteUser->getAvatar(),
        );
    }

    public static function fromProxyAuthRequest(Request $request): self
    {
        $identifier = $request->header(config('koel.proxy_auth.user_header'));

        return new self(
            provider: 'Reverse Proxy',
            id: $identifier,
            email: "$identifier@reverse-proxy",
            name: $request->header(config('koel.proxy_auth.preferred_name_header')) ?: $identifier,
            avatar: null,
        );
    }

    public static function assertValidProvider(string $provider): void
    {
        Assert::oneOf($provider, ['Google', 'Reverse Proxy']);
    }
}
