<?php

namespace Tests\Integration\Services;

use App\Services\AuthenticationService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

use function Tests\create_user;

class AuthenticationServiceTest extends TestCase
{
    private AuthenticationService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(AuthenticationService::class);
    }

    public function testTryResetPasswordUsingBroker(): void
    {
        Event::fake();
        $user = create_user();

        self::assertTrue(
            $this->service->tryResetPasswordUsingBroker($user->email, 'new-password', Password::createToken($user))
        );

        self::assertTrue(Hash::check('new-password', $user->fresh()->password));

        Event::assertDispatched(PasswordReset::class);
    }

    public function testTryResetPasswordUsingBrokerWithInvalidToken(): void
    {
        Event::fake();
        $user = create_user(['password' => Hash::make('old-password')]);

        self::assertFalse($this->service->tryResetPasswordUsingBroker($user->email, 'new-password', 'invalid-token'));
        self::assertTrue(Hash::check('old-password', $user->fresh()->password));
        Event::assertNotDispatched(PasswordReset::class);
    }
}
