<?php

namespace Tests\Unit\Services;

use App\Services\SecurityNoticeService;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

use function Tests\create_user;

class SecurityNoticeServiceTest extends TestCase
{
    #[Test]
    public function reportAFailedNoticeInsteadOfFailingTheCaller(): void
    {
        config(['mail.default' => 'smtp']);
        Exceptions::fake();
        Mail::shouldReceive('to->queue')->andThrow(new RuntimeException('SMTP is down'));

        (new SecurityNoticeService())->notifyPasswordChanged(create_user());

        Exceptions::assertReported(RuntimeException::class);
    }
}
