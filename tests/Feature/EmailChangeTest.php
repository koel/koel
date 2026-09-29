<?php

namespace Tests\Feature;

use App\Mail\ConfirmEmailChange;
use App\Mail\EmailChanged;
use App\Mail\EmailChangeRequested;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_user;

class EmailChangeTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config(['mail.default' => 'smtp']);
        Mail::fake();
    }

    private function requestEmailChange(User $user, string $newEmail): string
    {
        $this->putAs('api/me', ['name' => $user->name, 'email' => $newEmail], $user)->assertOk();

        $mail = Mail::queued(ConfirmEmailChange::class, static fn (ConfirmEmailChange $mail): bool => $mail->hasTo(
            $newEmail,
        ))->last();

        self::assertNotNull($mail);

        return $mail->confirmationUrl;
    }

    #[Test]
    public function keepTheOldEmailUntilTheNewOneIsConfirmed(): void
    {
        $user = create_user(['email' => 'old@koel.test']);

        $this->requestEmailChange($user, 'new@koel.test');

        self::assertSame('old@koel.test', $user->refresh()->email);
        Mail::assertQueued(EmailChangeRequested::class, static fn (EmailChangeRequested $mail): bool => $mail->hasTo(
            'old@koel.test',
        ));
    }

    #[Test]
    public function changeTheEmailRightAwayWithoutMail(): void
    {
        config(['mail.default' => 'log']);
        $user = create_user(['email' => 'old@koel.test']);

        $this->putAs('api/me', ['name' => $user->name, 'email' => 'new@koel.test'], $user)->assertOk();

        self::assertSame('new@koel.test', $user->refresh()->email);
        Mail::assertNothingQueued();
    }

    #[Test]
    public function changeTheEmailWithTheConfirmationLink(): void
    {
        $user = create_user(['email' => 'old@koel.test']);
        $confirmationUrl = $this->requestEmailChange($user, 'new@koel.test');

        $this->post($confirmationUrl)->assertOk();

        self::assertSame('new@koel.test', $user->refresh()->email);
    }

    #[Test]
    public function onlyShowTheConfirmationPageWhenTheLinkIsOpened(): void
    {
        $user = create_user(['email' => 'old@koel.test']);
        $confirmationUrl = $this->requestEmailChange($user, 'new@koel.test');

        $this->get($confirmationUrl)->assertOk()->assertSee('new@koel.test');

        self::assertSame('old@koel.test', $user->refresh()->email);
    }

    #[Test]
    public function refuseTheChangeOnceTheAccountUsesSingleSignOn(): void
    {
        $user = create_user(['email' => 'old@koel.test']);
        $confirmationUrl = $this->requestEmailChange($user, 'new@koel.test');
        $user->update(['sso_provider' => 'Google', 'sso_id' => '123']);

        $this->post($confirmationUrl)->assertOk();

        self::assertSame('old@koel.test', $user->refresh()->email);
    }

    #[Test]
    public function refuseATamperedLink(): void
    {
        $user = create_user(['email' => 'old@koel.test']);
        $confirmationUrl = $this->requestEmailChange($user, 'new@koel.test');

        $this->post(str_replace('new%40koel.test', 'attacker%40koel.test', $confirmationUrl))->assertForbidden();

        self::assertSame('old@koel.test', $user->refresh()->email);
    }

    #[Test]
    public function refuseAnExpiredLink(): void
    {
        $user = create_user(['email' => 'old@koel.test']);
        $confirmationUrl = $this->requestEmailChange($user, 'new@koel.test');

        $this->travel(25)->hours();

        $this->post($confirmationUrl)->assertForbidden();
        self::assertSame('old@koel.test', $user->refresh()->email);
    }

    #[Test]
    public function refuseALinkSentBeforeTheEmailChangedAgain(): void
    {
        $user = create_user(['email' => 'old@koel.test']);
        $firstLink = $this->requestEmailChange($user, 'first@koel.test');
        $secondLink = $this->requestEmailChange($user, 'second@koel.test');

        $this->post($secondLink)->assertOk();
        $this->post($firstLink)->assertOk();

        self::assertSame('second@koel.test', $user->refresh()->email);
    }

    #[Test]
    public function refuseAnAddressAnotherAccountTookMeanwhile(): void
    {
        $user = create_user(['email' => 'old@koel.test']);
        $confirmationUrl = $this->requestEmailChange($user, 'new@koel.test');
        create_user(['email' => 'new@koel.test']);

        $this->post($confirmationUrl)->assertOk();

        self::assertSame('old@koel.test', $user->refresh()->email);
    }

    #[Test]
    public function applyAnAdminsChangeRightAwayAndTellBothAddresses(): void
    {
        $user = create_user(['email' => 'old@koel.test']);

        $this->putAs(
            "api/users/{$user->public_id}",
            ['name' => $user->name, 'email' => 'new@koel.test', 'role' => 'user'],
            create_admin(),
        )->assertOk();

        self::assertSame('new@koel.test', $user->refresh()->email);
        Mail::assertNotQueued(ConfirmEmailChange::class);
        Mail::assertQueued(EmailChanged::class, static fn (EmailChanged $mail): bool => $mail->hasTo('old@koel.test'));
        Mail::assertQueued(
            EmailChanged::class,
            static fn (EmailChanged $mail): bool => (
                $mail->hasTo('new@koel.test')
                && $mail->newEmail === 'new@koel.test'
            ),
        );
    }
}
