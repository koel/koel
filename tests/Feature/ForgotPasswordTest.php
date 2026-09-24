<?php

namespace Tests\Feature;

use App\Services\Auth\AuthenticationService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class ForgotPasswordTest extends TestCase
{
    #[Test]
    public function sendResetPasswordRequest(): void
    {
        $this
            ->mock(AuthenticationService::class)
            ->expects('trySendResetPasswordLink')
            ->with('foo@bar.com')
            ->andReturnTrue();

        $this->postJson('/api/forgot-password', ['email' => 'foo@bar.com'])->assertNoContent();
    }

    #[Test]
    public function answerTheSameWayForAnAddressWithoutAnAccount(): void
    {
        $this
            ->mock(AuthenticationService::class)
            ->expects('trySendResetPasswordLink')
            ->with('foo@bar.com')
            ->andReturnFalse();

        $this->postJson('/api/forgot-password', ['email' => 'foo@bar.com'])->assertNoContent();
    }

    #[Test]
    public function resetLinkPointsToAppUrlWhateverTheRequestHost(): void
    {
        config(['app.url' => 'https://music.example.com']);
        Notification::fake();
        $user = create_user();

        $this
            ->withHeaders(['Host' => 'evil.example', 'X-Forwarded-Host' => 'evil.example'])
            ->postJson('/api/forgot-password', ['email' => $user->email])
            ->assertNoContent();

        Notification::assertSentTo($user, static function (ResetPassword $notification) use ($user): bool {
            return str_starts_with(
                $notification->toMail($user)->actionUrl,
                'https://music.example.com/#/reset-password/',
            );
        });
    }

    #[Test]
    public function resetPassword(): void
    {
        Event::fake();
        $user = create_user();

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'password' => 'new-password',
            'token' => Password::createToken($user),
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
            'token' => Password::createToken($user),
        ])->assertForbidden();
    }

    public function tearDown(): void
    {
        config(['koel.misc.demo' => false]);

        parent::tearDown();
    }
}
