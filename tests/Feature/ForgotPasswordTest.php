<?php

namespace Tests\Feature;

use App\Services\AuthenticationService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class ForgotPasswordTest extends TestCase
{
    #[Test]
    public function sendResetPasswordRequest(): void
    {
        $this->mock(AuthenticationService::class)
            ->expects('trySendResetPasswordLink')
            ->with('foo@bar.com')
            ->andReturnTrue();

        $this->postJson('/api/forgot-password', ['email' => 'foo@bar.com'])
            ->assertNoContent();
    }

    #[Test]
    public function sendResetPasswordRequestFailed(): void
    {
        $this->mock(AuthenticationService::class)
            ->expects('trySendResetPasswordLink')
            ->with('foo@bar.com')
            ->andReturnFalse();

        $this->postJson('/api/forgot-password', ['email' => 'foo@bar.com'])
            ->assertNotFound();
    }

    #[Test]
    public function resetPassword(): void
    {
        Event::fake();
        $user = create_user();

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'password' => 'new-password',
            'token' =>  Password::createToken($user),
        ])->assertNoContent();

        self::assertTrue(Hash::check('new-password', $user->refresh()->password));
        Event::assertDispatched(PasswordReset::class);
    }

    #[Test]
    public function resetPasswordFailed(): void
    {
        Event::fake();
        $user = create_user(['password' => Hash::make('old-password')]);

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'password' => 'new-password',
            'token' => 'invalid-token',
        ])->assertJsonValidationErrors([
            'token' => 'Invalid or expired token.',
        ]);

        self::assertTrue(Hash::check('old-password', $user->refresh()->password));
        Event::assertNotDispatched(PasswordReset::class);
    }

    #[Test]
    public function disabledInDemo(): void
    {
        config(['koel.misc.demo' => true]);

        $user = create_user();

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'password' => 'new-password',
            'token' =>  Password::createToken($user),
        ])->assertForbidden();
    }

    public function tearDown(): void
    {
        config(['koel.misc.demo' => false]);

        parent::tearDown();
    }
}
