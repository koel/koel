<?php

namespace Tests\Feature;

use App\Mail\PasswordChanged;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_user;

class SecurityNoticesTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config(['mail.default' => 'smtp']);
        Mail::fake();
    }

    private static function assertPasswordNoticeQueued(User $user): void
    {
        Mail::assertQueued(
            PasswordChanged::class,
            static fn (PasswordChanged $mail) => $mail->hasTo($user->email) && $mail->user->is($user),
        );
    }

    #[Test]
    public function stayQuietWhenUsersChangeTheirOwnPassword(): void
    {
        $user = create_user(['password' => Hash::make('old-secret')]);

        $this->putAs(
            'api/me/password',
            ['current_password' => 'old-secret', 'new_password' => 'new-secret-1234'],
            $user,
        )->assertNoContent();

        Mail::assertNotQueued(PasswordChanged::class);
    }

    #[Test]
    public function noticePasswordChangeByAnAdmin(): void
    {
        $user = create_user();

        $this->putAs(
            "api/users/{$user->public_id}",
            ['name' => $user->name, 'email' => $user->email, 'password' => 'new-secret-1234', 'role' => 'user'],
            create_admin(),
        )->assertSuccessful();

        self::assertPasswordNoticeQueued($user);
    }

    #[Test]
    public function stayQuietWhenAnAdminLeavesThePasswordAlone(): void
    {
        $user = create_user();

        $this->putAs(
            "api/users/{$user->public_id}",
            ['name' => 'Someone Else', 'email' => $user->email, 'role' => 'user'],
            create_admin(),
        )->assertSuccessful();

        Mail::assertNotQueued(PasswordChanged::class);
    }

    #[Test]
    public function noticePasswordReset(): void
    {
        $user = create_user();

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'password' => 'new-password',
            'token' => Password::createToken($user),
        ])->assertNoContent();

        self::assertPasswordNoticeQueued($user);
    }

    #[Test]
    public function noticePasswordChangeFromTheCommandLine(): void
    {
        $admin = create_admin();

        $this
            ->artisan('koel:admin:change-password')
            ->expectsQuestion('Your desired password', 'new-password')
            ->expectsQuestion('Again, just to be sure', 'new-password')
            ->assertSuccessful();

        self::assertPasswordNoticeQueued($admin);
    }

    #[Test]
    public function sendNothingWithoutAMailer(): void
    {
        config(['mail.default' => 'log']);
        $user = create_user();

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'password' => 'new-password',
            'token' => Password::createToken($user),
        ])->assertNoContent();

        Mail::assertNothingQueued();
    }
}
