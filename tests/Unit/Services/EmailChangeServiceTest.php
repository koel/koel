<?php

namespace Tests\Unit\Services;

use App\Enums\EmailChangeResult;
use App\Mail\ConfirmEmailChange;
use App\Repositories\UserRepository;
use App\Services\EmailChangeService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Uri;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class EmailChangeServiceTest extends TestCase
{
    private EmailChangeService $service;

    public function setUp(): void
    {
        parent::setUp();

        config(['mail.default' => 'smtp']);
        $this->service = app(EmailChangeService::class);
    }

    #[Test]
    public function requireConfirmationForANewEmailWhenMailIsConfigured(): void
    {
        self::assertTrue($this->service->requiresConfirmation(create_user([
            'email' => 'old@koel.test',
        ]), 'new@koel.test'));
    }

    #[Test]
    public function skipConfirmationWhenTheEmailStaysTheSame(): void
    {
        self::assertFalse($this->service->requiresConfirmation(create_user([
            'email' => 'old@koel.test',
        ]), 'old@koel.test'));
    }

    #[Test]
    public function skipConfirmationWithoutMail(): void
    {
        config(['mail.default' => 'log']);

        self::assertFalse($this->service->requiresConfirmation(create_user([
            'email' => 'old@koel.test',
        ]), 'new@koel.test'));
    }

    #[Test]
    public function skipConfirmationForSingleSignOnUsers(): void
    {
        $user = create_user(['email' => 'old@koel.test', 'sso_provider' => 'Google', 'sso_id' => '123']);

        self::assertFalse($this->service->requiresConfirmation($user, 'new@koel.test'));
    }

    #[Test]
    public function sendNoChangeNoticeWithoutMail(): void
    {
        config(['mail.default' => 'log']);
        Mail::fake();

        $this->service->notifyChange(create_user(['email' => 'new@koel.test']), 'old@koel.test');

        Mail::assertNothingQueued();
    }

    #[Test]
    public function reportTheAddressAsTakenWhenAnotherAccountClaimsItFirst(): void
    {
        $user = create_user(['email' => 'old@koel.test']);
        create_user(['email' => 'new@koel.test']);

        $repository = Mockery::mock(UserRepository::class);
        $repository->allows('findOneByEmail')->andReturnNull();

        Mail::fake();
        $service = new EmailChangeService($repository);
        $service->requestChange($user, 'new@koel.test');

        $confirmationUrl = Mail::queued(ConfirmEmailChange::class)->sole()->confirmationUrl;
        $signedApiPath = base64_decode(Str::after($confirmationUrl, 'email-change/'), true);
        $query = Uri::of($signedApiPath)->query();

        $result = $service->confirmChange($user, 'new@koel.test', $query->get('current'), $query->get('token'));

        self::assertSame(EmailChangeResult::TAKEN, $result);
        self::assertSame('old@koel.test', $user->refresh()->email);
    }
}
