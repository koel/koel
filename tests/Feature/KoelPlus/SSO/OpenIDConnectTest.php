<?php

namespace Tests\Feature\KoelPlus\SSO;

use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as OidcUser;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class OpenIDConnectTest extends PlusTestCase
{
    private static function mockOidcCallback(?string $ssoId = null): void
    {
        Socialite::expects('driver->user')->andReturn(Mockery::mock(OidcUser::class, [
            'getEmail' => 'bruce@iron.com',
            'getName' => 'Bruce Dickinson',
            'getAvatar' => null,
            'getId' => $ssoId ?? Str::random(),
        ]));
    }

    private function assertCallbackIssuesToken(): void
    {
        $this->get('auth/oidc/callback')->assertOk()->assertViewIs('sso-callback')->assertViewHas('token');
    }

    #[Test]
    public function callbackWithNewUser(): void
    {
        self::mockOidcCallback();

        $this->assertCallbackIssuesToken();
    }

    #[Test]
    public function callbackWithExistingEmail(): void
    {
        create_user(['email' => 'bruce@iron.com']);

        self::mockOidcCallback();

        $this->assertCallbackIssuesToken();
    }

    #[Test]
    public function callbackWithExistingSSOUser(): void
    {
        create_user([
            'sso_provider' => 'OpenID Connect',
            'sso_id' => '123',
            'email' => 'bruce@iron.com',
        ]);

        self::mockOidcCallback('123');

        $this->assertCallbackIssuesToken();
    }

    #[Test]
    public function callbackBypassesTwoFactorChallenge(): void
    {
        create_user([
            'sso_provider' => 'OpenID Connect',
            'sso_id' => '123',
            'email' => 'bruce@iron.com',
            'two_factor_confirmed_at' => now(),
        ]);

        self::mockOidcCallback('123');

        $this->assertCallbackIssuesToken();
    }
}
