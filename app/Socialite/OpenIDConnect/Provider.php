<?php

namespace App\Socialite\OpenIDConnect;

use GuzzleHttp\Client;
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

        $response = (new Client())->get($issuer . '/.well-known/openid-configuration');
        $this->discovery = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

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
        $response = (new Client())->get($this->discover()['userinfo_endpoint'], [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        return json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
    }

    /** @inheritdoc */
    protected function mapUserToObject(array $user): User
    {
        $instance = new User();

        return $instance->setRaw($user)->map([
            'id' => $user['sub'],
            'nickname' => $user['preferred_username'] ?? null,
            'name' => $user['name'] ?? $user['preferred_username'] ?? $user['email'] ?? $user['sub'],
            'email' => $user['email'] ?? null,
            'avatar' => $user['picture'] ?? null,
        ]);
    }

    private function getConfig(string $key): mixed
    {
        return $this->config[$key] ?? null;
    }
}
