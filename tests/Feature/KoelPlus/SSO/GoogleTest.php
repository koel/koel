<?php

namespace Tests\Feature\KoelPlus\SSO;

use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use Tests\PlusTestCase;

use function Tests\create_user;

class GoogleTest extends PlusTestCase
{
    public function testCallbackWithNewUser(): void
    {
        $googleUser = Mockery::mock(GoogleUser::class, [
            'getEmail' => 'bruce@iron.com',
            'getName' => 'Bruce Dickinson',
            'getAvatar' => 'https://lh3.googleusercontent.com/a/vatar',
            'getId' => Str::random(),
        ]);

        Socialite::shouldReceive('driver->user')->andReturn($googleUser);

        $response = $this->get('auth/google/callback');
        $response->assertOk();
        $response->assertViewIs('sso-callback');
        $response->assertViewHas('token');
    }

    public function testCallbackWithExistingEmail(): void
    {
        create_user(['email' => 'bruce@iron.com']);

        $googleUser = Mockery::mock(GoogleUser::class, [
            'getEmail' => 'bruce@iron.com',
            'getName' => 'Bruce Dickinson',
            'getAvatar' => 'https://lh3.googleusercontent.com/a/vatar',
            'getId' => Str::random(),
        ]);

        Socialite::shouldReceive('driver->user')->andReturn($googleUser);

        $response = $this->get('auth/google/callback');
        $response->assertOk();
        $response->assertViewIs('sso-callback');
        $response->assertViewHas('token');
    }

    public function testCallbackWithExistingSSOUser(): void
    {
        create_user([
            'sso_provider' => 'Google',
            'sso_id' => '123',
            'email' => 'bruce@iron.com',
        ]);

        $googleUser = Mockery::mock(GoogleUser::class, [
            'getEmail' => 'bruce@iron.com',
            'getName' => 'Bruce Dickinson',
            'getAvatar' => 'https://lh3.googleusercontent.com/a/vatar',
            'getId' => '123',
        ]);

        Socialite::shouldReceive('driver->user')->andReturn($googleUser);

        $response = $this->get('auth/google/callback');
        $response->assertOk();
        $response->assertViewIs('sso-callback');
        $response->assertViewHas('token');
    }
}
