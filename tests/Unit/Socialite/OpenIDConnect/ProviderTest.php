<?php

namespace Tests\Unit\Socialite\OpenIDConnect;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProviderTest extends TestCase
{
    private TestableProvider $provider;

    public function setUp(): void
    {
        parent::setUp();

        $this->provider = new TestableProvider(
            Request::create('/auth/oidc/callback'),
            'client-id',
            'client-secret',
            'http://localhost/auth/oidc/callback',
            'https://idp.example.com',
        );
    }

    #[Test]
    public function mapsVerifiedEmail(): void
    {
        $mapped = $this->provider->mapUserPublic([
            'sub' => 'user-123',
            'email' => 'bruce@iron.com',
            'email_verified' => true,
            'name' => 'Bruce Dickinson',
            'preferred_username' => 'bruce',
        ]);

        self::assertSame('user-123', $mapped->getId());
        self::assertSame('bruce@iron.com', $mapped->getEmail());
        self::assertSame('Bruce Dickinson', $mapped->getName());
        self::assertSame('bruce', $mapped->getNickname());
    }

    #[Test]
    public function dropsEmailWhenUnverified(): void
    {
        $mapped = $this->provider->mapUserPublic([
            'sub' => 'user-123',
            'email' => 'bruce@iron.com',
            'email_verified' => false,
            'name' => 'Bruce Dickinson',
        ]);

        self::assertNull($mapped->getEmail());
        self::assertSame('user-123', $mapped->getId());
    }

    #[Test]
    public function dropsEmailWhenVerifiedClaimMissing(): void
    {
        $mapped = $this->provider->mapUserPublic([
            'sub' => 'user-123',
            'email' => 'bruce@iron.com',
            'name' => 'Bruce Dickinson',
        ]);

        self::assertNull($mapped->getEmail());
    }

    #[Test]
    public function fallsBackToFirstAvailableNameClaim(): void
    {
        $mapped = $this->provider->mapUserPublic([
            'sub' => 'user-123',
            'preferred_username' => 'bruce',
        ]);

        self::assertSame('bruce', $mapped->getName());
    }
}
