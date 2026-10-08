<?php

namespace Tests\Feature\Auth;

use App\Http\Resources\PasskeyResource;
use App\Models\Passkey;
use App\Models\User;
use App\Services\Auth\TwoFactorAuthenticator;
use Laravel\Passkeys\Actions\StorePasskey;
use Laravel\Passkeys\Actions\VerifyPasskey;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class PasskeyTest extends TestCase
{
    #[Test]
    public function getLoginOptions(): void
    {
        $this
            ->getJson('api/me/passkey-login-options')
            ->assertOk()
            ->assertJsonStructure(['options' => ['challenge'], 'login_token']);
    }

    #[Test]
    public function logInWithPasskey(): void
    {
        $passkey = Passkey::factory()->createOne();
        $this->mock(VerifyPasskey::class)->expects('__invoke')->andReturn($passkey);

        $this
            ->postJson('api/me/passkey-login', [
                'login_token' => $this->getJson('api/me/passkey-login-options')->json('login_token'),
                'credential' => self::assertionCredential(),
            ])
            ->assertOk()
            ->assertJsonStructure(['token', 'audio-token']);
    }

    #[Test]
    public function logInWithPasskeySkipsTwoFactorChallenge(): void
    {
        $user = self::createUserWithTwoFactorEnabled();
        $passkey = Passkey::factory()->for($user)->createOne();
        $this->mock(VerifyPasskey::class)->expects('__invoke')->andReturn($passkey);

        $response = $this->postJson('api/me/passkey-login', [
            'login_token' => $this->getJson('api/me/passkey-login-options')->json('login_token'),
            'credential' => self::assertionCredential(),
        ])->assertOk();

        self::assertNotEmpty($response->json('token'));
        self::assertNull($response->json('two_factor'));
    }

    #[Test]
    public function logInWithUnknownLoginToken(): void
    {
        $this->postJson('api/me/passkey-login', [
            'login_token' => 'unknown',
            'credential' => self::assertionCredential(),
        ])->assertUnauthorized();
    }

    #[Test]
    public function listOwnPasskeys(): void
    {
        $user = create_user();
        Passkey::factory()->for($user)->createMany(2);
        Passkey::factory()->createOne();

        $this
            ->getAs('api/me/passkeys', $user)
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonStructure([0 => PasskeyResource::JSON_STRUCTURE]);
    }

    #[Test]
    public function registerPasskey(): void
    {
        $user = create_user();
        $passkey = Passkey::factory()->for($user)->createOne(['name' => 'MacBook']);
        $this->mock(StorePasskey::class)->expects('__invoke')->andReturn($passkey);

        $this
            ->getAs('api/me/passkeys/registration-options', $user)
            ->assertOk()
            ->assertJsonStructure(['challenge', 'rp', 'user']);

        $this
            ->postAs('api/me/passkeys', ['name' => 'MacBook', 'credential' => self::attestationCredential()], $user)
            ->assertCreated()
            ->assertJsonStructure(PasskeyResource::JSON_STRUCTURE);
    }

    #[Test]
    public function registerPasskeyWithoutRegistrationOptions(): void
    {
        $this->postAs('api/me/passkeys', [
            'name' => 'MacBook',
            'credential' => self::attestationCredential(),
        ])->assertUnprocessable();
    }

    #[Test]
    public function deleteOwnPasskey(): void
    {
        $user = create_user();
        $passkey = Passkey::factory()->for($user)->createOne();

        $this->deleteAs("api/me/passkeys/{$passkey->id}", [], $user)->assertNoContent();

        $this->assertModelMissing($passkey);
    }

    #[Test]
    public function cannotDeleteSomeoneElsesPasskey(): void
    {
        $passkey = Passkey::factory()->createOne();

        $this->deleteAs("api/me/passkeys/{$passkey->id}", [], create_user())->assertForbidden();

        $this->assertModelExists($passkey);
    }

    /** @return array<string, mixed> */
    private static function assertionCredential(): array
    {
        return [
            'id' => self::base64Url('credential'),
            'rawId' => self::base64Url('credential'),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => self::clientDataJson('webauthn.get'),
                'authenticatorData' => self::base64Url(self::authenticatorData()),
                'signature' => self::base64Url('signature'),
                'userHandle' => null,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function attestationCredential(): array
    {
        $authenticatorData = self::authenticatorData();

        $attestationObject =
            "\xa3"
            . "\x63fmt\x64none"
            . "\x67attStmt\xa0"
            . "\x68authData\x58"
            . chr(strlen($authenticatorData))
            . $authenticatorData;

        return [
            'id' => self::base64Url('credential'),
            'rawId' => self::base64Url('credential'),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => self::clientDataJson('webauthn.create'),
                'attestationObject' => self::base64Url($attestationObject),
                'transports' => [],
            ],
        ];
    }

    private static function clientDataJson(string $type): string
    {
        return self::base64Url(json_encode([
            'type' => $type,
            'challenge' => self::base64Url('challenge'),
            'origin' => 'http://localhost',
        ]));
    }

    private static function authenticatorData(): string
    {
        return hash('sha256', 'localhost', true) . chr(0x05) . pack('N', 1);
    }

    private static function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function createUserWithTwoFactorEnabled(): User
    {
        $user = create_user();

        $twoFactorAuth = app(TwoFactorAuthenticator::class);
        $twoFactorAuth->enroll($user);
        $twoFactorAuth->confirm($user, $twoFactorAuth->generateRecoveryCodes());

        return $user->refresh();
    }
}
