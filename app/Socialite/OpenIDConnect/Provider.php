<?php

namespace App\Socialite\OpenIDConnect;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;
use RuntimeException;
use SensitiveParameter;

class Provider extends AbstractProvider
{
    public const string IDENTIFIER = 'OPENID';

    /** @var array<string> */
    protected $scopes = ['openid', 'email', 'profile'];

    protected $scopeSeparator = ' ';

    /** @var array<string, mixed>|null */
    private ?array $discovery = null;

    public static function additionalConfigKeys(): array
    {
        return ['issuer'];
    }

    /** @return array<string, mixed> */
    private function discover(): array
    {
        if ($this->discovery !== null) {
            return $this->discovery;
        }

        $issuer = rtrim((string) $this->getConfig('issuer'), '/');

        throw_unless($issuer, new RuntimeException('OIDC issuer URL is not configured.'));

        $this->discovery = Http::get($issuer . '/.well-known/openid-configuration')->throw()->json();

        return $this->discovery;
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->discover()['authorization_endpoint'], $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->discover()['token_endpoint'];
    }

    /** @inheritdoc */
    protected function getUserByToken(#[SensitiveParameter] $token): array
    {
        return Http::withToken($token)
            ->get($this->discover()['userinfo_endpoint'])
            ->throw()
            ->json();
    }

    /** @inheritdoc */
    protected function mapUserToObject(array $user): User
    {
        $instance = new User();

        $name = Arr::first(
            ['name', 'preferred_username', 'email', 'sub'],
            static fn (string $key): bool => filled(Arr::get($user, $key)),
        );

        return $instance->setRaw($user)->map([
            'id' => Arr::get($user, 'sub'),
            'nickname' => Arr::get($user, 'preferred_username'),
            'name' => Arr::get($user, $name),
            'email' => Arr::get($user, 'email'),
            'avatar' => Arr::get($user, 'picture'),
        ]);
    }

    private function getConfig(string $key): mixed
    {
        return $this->config[$key] ?? null;
    }
}
