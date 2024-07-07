<?php

namespace Tests\Unit\Services;

use App\Repositories\UserRepository;
use App\Services\AuthenticationService;
use App\Services\TokenManager;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Password;
use Mockery\MockInterface;
use Tests\TestCase;

class AuthenticationServiceTest extends TestCase
{
    private UserRepository|MockInterface $userRepository;
    private TokenManager|MockInterface $tokenManager;
    private PasswordBroker|MockInterface $passwordBroker;
    private AuthenticationService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->mock(UserRepository::class);
        $this->tokenManager = $this->mock(TokenManager::class);
        $this->passwordBroker = $this->mock(PasswordBroker::class);

        $this->service = new AuthenticationService(
            $this->userRepository,
            $this->tokenManager,
            $this->passwordBroker
        );
    }

    public function testTrySendResetPasswordLink(): void
    {
        $this->passwordBroker
            ->shouldReceive('sendResetLink')
            ->with(['email' => 'foo@bar.com'])
            ->andReturn(Password::RESET_LINK_SENT);

        $this->assertTrue($this->service->trySendResetPasswordLink('foo@bar.com'));
    }
}
