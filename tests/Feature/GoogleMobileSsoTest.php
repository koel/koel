<?php

namespace Tests\Feature;

use App\Services\GoogleIdTokenVerifier;
use App\Values\User\SsoUser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use UnexpectedValueException;

use function Tests\create_user;

class GoogleMobileSsoTest extends TestCase
{
    private function mockVerifier(?SsoUser $returnUser = null, bool $throws = false): void
    {
        $mock = $this->mock(GoogleIdTokenVerifier::class);

        if ($throws) {
            $mock->shouldReceive('verify')->andThrow(new UnexpectedValueException('Invalid token'));
        } else {
            $mock->shouldReceive('verify')->andReturn($returnUser);
        }
    }

    private function ssoUser(): SsoUser
    {
        return SsoUser::fromArray([
            'provider' => 'Google',
            'id' => 'google-123',
            'email' => 'test@gmail.com',
            'name' => 'Test User',
            'avatar' => 'https://lh3.googleusercontent.com/photo.jpg',
        ]);
    }

    #[Test]
    public function loginWithExistingUser(): void
    {
        $ssoUser = $this->ssoUser();

        create_user([
            'email' => $ssoUser->email,
            'sso_id' => $ssoUser->id,
            'sso_provider' => 'Google',
        ]);

        $this->mockVerifier($ssoUser);

        $this
            ->post('api/me/google', ['id_token' => 'valid-token'])
            ->assertOk()
            ->assertJsonStructure(['token', 'audio-token']);
    }

    #[Test]
    public function loginWithNewUserRequiresConsent(): void
    {
        $this->mockVerifier($this->ssoUser());

        $this
            ->post('api/me/google', ['id_token' => 'valid-token'])
            ->assertOk()
            ->assertJsonStructure([
                'requires_consent',
                'sso_user' => ['provider', 'id', 'email', 'name', 'avatar'],
                'legal_urls' => ['terms_url', 'privacy_url'],
            ])
            ->assertJson(['requires_consent' => true]);
    }

    #[Test]
    public function loginWithInvalidTokenReturns401(): void
    {
        $this->mockVerifier(throws: true);

        $this
            ->post('api/me/google', ['id_token' => 'invalid-token'])
            ->assertUnauthorized();
    }

    #[Test]
    public function loginWithoutIdTokenReturns422(): void
    {
        $this
            ->postJson('api/me/google', [])
            ->assertUnprocessable();
    }

    #[Test]
    public function consentCreatesUserAndReturnsTokens(): void
    {
        $ssoUser = $this->ssoUser();

        $this
            ->post('api/me/google/consent', [
                'sso_user' => $ssoUser->toArray(),
                'terms_accepted' => true,
                'privacy_accepted' => true,
                'age_verified' => true,
            ])
            ->assertOk()
            ->assertJsonStructure(['token', 'audio-token']);

        $this->assertDatabaseHas('users', [
            'email' => $ssoUser->email,
            'sso_id' => $ssoUser->id,
            'sso_provider' => 'Google',
        ]);
    }

    #[Test]
    public function consentFailsWithoutRequiredFields(): void
    {
        $this
            ->postJson('api/me/google/consent', [
                'sso_user' => $this->ssoUser()->toArray(),
            ])
            ->assertUnprocessable();
    }
}
