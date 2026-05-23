<?php

namespace App\Socialite\OpenIDConnect;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;
use SensitiveParameter;

class Provider extends AbstractProvider
{
    public const string IDENTIFIER = 'OPENID';

    private const int CONNECT_TIMEOUT_SECONDS = 5;
    private const int REQUEST_TIMEOUT_SECONDS = 10;

    /** @var array<string> */
    protected $scopes = ['openid', 'email', 'profile'];

    protected $scopeSeparator = ' ';

    /** @var array<string, mixed>|null */
    private ?array $discovery = null;

    public function __construct(
        Request $request,
        string $clientId,
        #[SensitiveParameter]
        string $clientSecret,
        string $redirectUrl,
        private readonly string $issuer,
    ) {
        parent::__construct($request, $clientId, $clientSecret, $redirectUrl);
    }

    /** @return array<string, mixed> */
    private function discover(): array
    {
        $this->discovery ??= Http::connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
            ->timeout(self::REQUEST_TIMEOUT_SECONDS)
            ->get(Str::finish($this->issuer, '/') . '.well-known/openid-configuration')
            ->throw()
            ->json();

        return $this->discovery;
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(Arr::get($this->discover(), 'authorization_endpoint'), $state);
    }

    protected function getTokenUrl(): string
    {
        return Arr::get($this->discover(), 'token_endpoint');
    }

    /** @inheritdoc */
    protected function getUserByToken(#[SensitiveParameter] $token): array
    {
        return Http::withToken($token)
            ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
            ->timeout(self::REQUEST_TIMEOUT_SECONDS)
            ->get(Arr::get($this->discover(), 'userinfo_endpoint'))
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

        $email = Arr::get($user, 'email_verified') === true ? Arr::get($user, 'email') : null;

        return $instance->setRaw($user)->map([
            'id' => Arr::get($user, 'sub'),
            'nickname' => Arr::get($user, 'preferred_username'),
            'name' => Arr::get($user, $name),
            'email' => $email,
            'avatar' => Arr::get($user, 'picture'),
        ]);
    }
}
