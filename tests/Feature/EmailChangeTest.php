<?php

namespace Tests\Feature;

use App\Mail\ConfirmEmailChange;
use App\Mail\EmailChanged;
use App\Mail\EmailChangeRequested;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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

        return 'api/' . base64_decode(Str::after($mail->confirmationUrl, 'email-change/'), true);
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

        $this->postJson($confirmationUrl)->assertNoContent();

        self::assertSame('new@koel.test', $user->refresh()->email);
    }

    #[Test]
    public function neverChangeTheEmailWhenTheLinkIsMerelyOpened(): void
    {
        $user = create_user(['email' => 'old@koel.test']);
        $confirmationUrl = $this->requestEmailChange($user, 'new@koel.test');

        $this->getJson($confirmationUrl)->assertClientError();

        self::assertSame('old@koel.test', $user->refresh()->email);
    }

    #[Test]
    public function refuseTheChangeOnceTheAccountUsesSingleSignOn(): void
    {
        $user = create_user(['email' => 'old@koel.test']);
        $confirmationUrl = $this->requestEmailChange($user, 'new@koel.test');
        $user->update(['sso_provider' => 'Google', 'sso_id' => '123']);

        $this->postJson($confirmationUrl)->assertForbidden();

        self::assertSame('old@koel.test', $user->refresh()->email);
    }

    #[Test]
    public function refuseATamperedLink(): void
    {
        $user = create_user(['email' => 'old@koel.test']);
        $confirmationUrl = $this->requestEmailChange($user, 'new@koel.test');

        $this->postJson(str_replace('new%40koel.test', 'attacker%40koel.test', $confirmationUrl))->assertForbidden();

        self::assertSame('old@koel.test', $user->refresh()->email);
    }

    #[Test]
    public function ignoreAnAddressSentOutsideTheSignedLink(): void
    {
        $user = create_user(['email' => 'old@koel.test']);
        $confirmationUrl = $this->requestEmailChange($user, 'new@koel.test');

        $this->postJson($confirmationUrl, ['email' => 'unconfirmed@koel.test'])->assertNoContent();

        self::assertSame('new@koel.test', $user->refresh()->email);
    }

    #[Test]
    public function refuseAnExpiredLink(): void
    {
        $user = create_user(['email' => 'old@koel.test']);
        $confirmationUrl = $this->requestEmailChange($user, 'new@koel.test');

        $this->travel(25)->hours();

        $this->postJson($confirmationUrl)->assertForbidden();
        self::assertSame('old@koel.test', $user->refresh()->email);
    }

    #[Test]
    public function refuseALinkSentBeforeTheEmailChangedAgain(): void
    {
        $user = create_user(['email' => 'old@koel.test']);
        $firstLink = $this->requestEmailChange($user, 'first@koel.test');
        $secondLink = $this->requestEmailChange($user, 'second@koel.test');

        $this->postJson($secondLink)->assertNoContent();
        $this->postJson($firstLink)->assertForbidden();

        self::assertSame('second@koel.test', $user->refresh()->email);
    }

    #[Test]
    public function refuseAnOlderLinkOnceANewerOneWasSent(): void
    {
        $user = create_user(['email' => 'old@koel.test']);
        $firstLink = $this->requestEmailChange($user, 'typo@koel.test');
        $secondLink = $this->requestEmailChange($user, 'fixed@koel.test');

        $this->postJson($firstLink)->assertForbidden();
        self::assertSame('old@koel.test', $user->refresh()->email);

        $this->postJson($secondLink)->assertNoContent();
        self::assertSame('fixed@koel.test', $user->refresh()->email);
    }

    #[Test]
    public function refuseAnAddressAnotherAccountTookMeanwhile(): void
    {
        $user = create_user(['email' => 'old@koel.test']);
        $confirmationUrl = $this->requestEmailChange($user, 'new@koel.test');
        create_user(['email' => 'new@koel.test']);

        $this->postJson($confirmationUrl)->assertConflict();

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
