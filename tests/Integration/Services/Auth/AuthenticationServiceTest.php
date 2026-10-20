<?php

namespace Tests\Integration\Services\Auth;

use App\Models\User;
use App\Services\Auth\AuthenticationService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function tryResetPasswordUsingBroker(): void
    {
        Event::fake();
        $user = create_user();

        self::assertTrue($this->service->tryResetPasswordUsingBroker(
            $user->email,
            'new-password',
            Password::createToken($user),
        ));

        self::assertTrue(Hash::check('new-password', $user->fresh()->password));

        Event::assertDispatched(PasswordReset::class);
    }

    #[Test]
    public function tryResetPasswordUsingBrokerWithInvalidToken(): void
    {
        Event::fake();
        $user = create_user(['password' => Hash::make('old-password')]);

        self::assertFalse($this->service->tryResetPasswordUsingBroker($user->email, 'new-password', 'invalid-token'));
        self::assertTrue(Hash::check('old-password', $user->fresh()->password));
        Event::assertNotDispatched(PasswordReset::class);
    }

    #[Test]
    public function resettingPasswordAcceptsPendingInvitation(): void
    {
        Event::fake();
        $prospect = User::factory()->prospect()->createOne();

        self::assertTrue($this->service->tryResetPasswordUsingBroker(
            $prospect->email,
            'new-password',
            Password::createToken($prospect),
        ));

        $prospect->refresh();

        self::assertFalse($prospect->is_prospect);
        self::assertNotNull($prospect->invitation_accepted_at);
    }
}
