<?php

namespace App\Values\User;

use Illuminate\Http\Request;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Webmozart\Assert\Assert;

final readonly class SsoUser
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
        $email = filter_var($identifier, FILTER_VALIDATE_EMAIL) ?: "$identifier@reverse.proxy";

        return new self(
            provider: 'Reverse Proxy',
            id: $identifier,
            email: $email,
            name: $request->header(config('koel.proxy_auth.preferred_name_header')) ?: $identifier,
            avatar: null,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            provider: $data['provider'],
            id: $data['id'],
            email: $data['email'],
            name: $data['name'],
            avatar: $data['avatar'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'avatar' => $this->avatar,
        ];
    }

    public static function assertValidProvider(string $provider): void
    {
        Assert::oneOf($provider, ['Google', 'Reverse Proxy']);
    }
}
