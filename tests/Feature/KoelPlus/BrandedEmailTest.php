<?php

namespace Tests\Feature\KoelPlus;

use App\Mail\ConfirmEmailChange;
use App\Mail\EmailChanged;
use App\Mail\EmailChangeRequested;
use App\Mail\UserInvite;
use App\Models\Organization;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class BrandedEmailTest extends PlusTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Setting::set('branding', ['name' => 'Little Bird'], Organization::default());
    }

    #[Test]
    public function brandTheInvitation(): void
    {
        $invitee = User::factory()->prospect()->createOne();

        (new UserInvite($invitee))
            ->assertHasSubject('Invitation to join Little Bird')
            ->assertSeeInHtml('join them on Little Bird')
            ->assertSeeInHtml('© ' . date('Y') . ' Little Bird.');
    }

    #[Test]
    public function brandThePasswordResetEmail(): void
    {
        $mail = (new ResetPassword('reset-token'))->toMail(create_user());

        self::assertSame('Reset your Little Bird password', $mail->subject);
        self::assertStringContainsString('your Little Bird account', (string) $mail->render());
    }

    #[Test]
    public function brandTheEmailChangeEmails(): void
    {
        $user = create_user();

        (new ConfirmEmailChange($user, 'new@koel.test', 'https://koel.test/confirm'))->assertSeeInHtml(
            'your Little Bird account',
        );
        (new EmailChangeRequested($user, 'new@koel.test'))->assertSeeInHtml('your Little Bird account');
        (new EmailChanged($user, 'old@koel.test', 'new@koel.test'))->assertSeeInHtml('your Little Bird account');
    }
}
