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
    #[Test]
    public function callbackWithNewUser(): void
    {
        $oidcUser = Mockery::mock(OidcUser::class, [
            'getEmail' => 'bruce@iron.com',
            'getName' => 'Bruce Dickinson',
            'getAvatar' => null,
            'getId' => Str::random(),
        ]);

        Socialite::expects('driver->user')->andReturn($oidcUser);

        $response = $this->get('auth/oidc/callback');
        $response->assertOk();
        $response->assertViewIs('sso-callback');
        $response->assertViewHas('token');
    }

    #[Test]
    public function callbackWithExistingEmail(): void
    {
        create_user(['email' => 'bruce@iron.com']);

        $oidcUser = Mockery::mock(OidcUser::class, [
            'getEmail' => 'bruce@iron.com',
            'getName' => 'Bruce Dickinson',
            'getAvatar' => null,
            'getId' => Str::random(),
        ]);

        Socialite::expects('driver->user')->andReturn($oidcUser);

        $response = $this->get('auth/oidc/callback');
        $response->assertOk();
        $response->assertViewIs('sso-callback');
        $response->assertViewHas('token');
    }

    #[Test]
    public function callbackWithExistingSSOUser(): void
    {
        create_user([
            'sso_provider' => 'OpenID Connect',
            'sso_id' => '123',
            'email' => 'bruce@iron.com',
        ]);

        $oidcUser = Mockery::mock(OidcUser::class, [
            'getEmail' => 'bruce@iron.com',
            'getName' => 'Bruce Dickinson',
            'getAvatar' => null,
            'getId' => '123',
        ]);

        Socialite::expects('driver->user')->andReturn($oidcUser);

        $response = $this->get('auth/oidc/callback');
        $response->assertOk();
        $response->assertViewIs('sso-callback');
        $response->assertViewHas('token');
    }
}
