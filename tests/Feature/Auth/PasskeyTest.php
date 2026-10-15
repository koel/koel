<?php

namespace Tests\Feature\Auth;

use App\Http\Resources\PasskeyResource;
use App\Models\Passkey;
use App\Models\User;
use App\Services\Auth\TwoFactorAuthenticator;
use Laravel\Passkeys\Actions\StorePasskey;
use Laravel\Passkeys\Actions\VerifyPasskey;
use Laravel\Passkeys\Passkeys;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Webauthn\Exception\AuthenticatorResponseVerificationException;

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
        [$user] = self::createUserWithTwoFactorEnabled();
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
    public function logInWithPasskeyTheLibraryRejects(): void
    {
        $this
            ->mock(VerifyPasskey::class)
            ->expects('__invoke')
            ->andThrow(AuthenticatorResponseVerificationException::create('Invalid signature.'));

        $this->postJson('api/me/passkey-login', [
            'login_token' => $this->getJson('api/me/passkey-login-options')->json('login_token'),
            'credential' => self::assertionCredential(),
        ])->assertUnprocessable();
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
            ->postAs('api/me/passkeys/registration-options', ['password' => 'secret'], $user)
            ->assertOk()
            ->assertJsonStructure(['challenge', 'rp', 'user']);

        $this
            ->postAs('api/me/passkeys', ['name' => 'MacBook', 'credential' => self::attestationCredential()], $user)
            ->assertCreated()
            ->assertJsonStructure(PasskeyResource::JSON_STRUCTURE);
    }

    #[Test]
    public function getRegistrationOptionsWithWrongPassword(): void
    {
        $this
            ->postAs('api/me/passkeys/registration-options', ['password' => 'wrong'], create_user())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    #[Test]
    public function getRegistrationOptionsWithTwoFactorEnabled(): void
    {
        [$user, $recoveryCodes] = self::createUserWithTwoFactorEnabled();

        $this
            ->postAs('api/me/passkeys/registration-options', ['password' => 'secret'], $user)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');

        $this->postAs(
            'api/me/passkeys/registration-options',
            ['password' => 'secret', 'code' => $recoveryCodes[0]],
            $user,
        )->assertOk();
    }

    #[Test]
    public function getRegistrationOptionsConfirmingWithPasskey(): void
    {
        $user = create_user();
        $passkey = Passkey::factory()->for($user)->createOne();

        $this
            ->mock(VerifyPasskey::class)
            ->expects('__invoke')
            ->withArgs(static fn ($credential, $options, $owner): bool => $owner->is($user))
            ->andReturn($passkey);

        $this->getAs('api/me/passkeys/confirmation-options', $user)->assertOk()->assertJsonStructure(['challenge']);

        $this->postAs(
            'api/me/passkeys/registration-options',
            ['credential' => self::assertionCredential()],
            $user,
        )->assertOk();
    }

    #[Test]
    public function getRegistrationOptionsAsSsoUserWithoutPasskeys(): void
    {
        $this->postAs('api/me/passkeys/registration-options', [], create_user([
            'sso_provider' => 'Google',
        ]))->assertOk();
    }

    #[Test]
    public function getRegistrationOptionsAsSsoUserWithPasskeysRequiresConfirmation(): void
    {
        $user = create_user(['sso_provider' => 'Google']);
        Passkey::factory()->for($user)->createOne();

        $this->postAs('api/me/passkeys/registration-options', [], $user)->assertUnprocessable();
    }

    #[Test]
    public function registerPasskeyWithoutRegistrationOptions(): void
    {
        $this->postAs('api/me/passkeys', [
            'name' => 'MacBook',
            'credential' => self::attestationCredential(),
        ])->assertUnprocessable();
    }

    /** @return array<string, array<string>> */
    public static function addressesUnderRelyingPartyIdDataProvider(): array
    {
        return [
            'another port' => ['http://localhost:8001'],
            'subdomain' => ['http://music.localhost'],
        ];
    }

    #[Test]
    #[DataProvider('addressesUnderRelyingPartyIdDataProvider')]
    public function registerPasskeyFromAddressUnderRelyingPartyId(string $address): void
    {
        $user = create_user();
        $passkey = Passkey::factory()->for($user)->createOne();

        $this
            ->mock(StorePasskey::class)
            ->expects('__invoke')
            ->withArgs(static fn (): bool => in_array($address, Passkeys::allowedOrigins(), true))
            ->andReturn($passkey);

        $this->postAs("$address/api/me/passkeys/registration-options", ['password' => 'secret'], $user)->assertOk();

        $this->postAs(
            "$address/api/me/passkeys",
            ['name' => 'MacBook', 'credential' => self::attestationCredential($address)],
            $user,
        )->assertCreated();
    }

    #[Test]
    public function registerPasskeyWithConfiguredOriginFromAnotherAddress(): void
    {
        $user = create_user();
        $passkey = Passkey::factory()->for($user)->createOne();
        $this->mock(StorePasskey::class)->expects('__invoke')->andReturn($passkey);

        $this->postAs('api/me/passkeys/registration-options', ['password' => 'secret'], $user)->assertOk();

        $this->postAs(
            'http://localhost:8001/api/me/passkeys',
            ['name' => 'MacBook', 'credential' => self::attestationCredential('http://localhost')],
            $user,
        )->assertCreated();
    }

    /** @return array<string, array<string>> */
    public static function mismatchedAddressesDataProvider(): array
    {
        return [
            'another port than the request' => ['http://localhost', 'http://localhost:8001'],
            'another subdomain than the request' => ['http://music.localhost', 'http://admin.localhost'],
            'another scheme than the request' => ['http://localhost:8001', 'https://localhost:8001'],
            'a host outside the relying party ID' => ['http://example.com', 'http://example.com'],
        ];
    }

    #[Test]
    #[DataProvider('mismatchedAddressesDataProvider')]
    public function registerPasskeyFromMismatchedAddress(string $requestAddress, string $credentialOrigin): void
    {
        $user = create_user();
        $this->mock(StorePasskey::class)->shouldNotReceive('__invoke');

        $this->postAs('api/me/passkeys/registration-options', ['password' => 'secret'], $user)->assertOk();

        $this
            ->postAs(
                "$requestAddress/api/me/passkeys",
                ['name' => 'MacBook', 'credential' => self::attestationCredential($credentialOrigin)],
                $user,
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['credential' => "Passkeys can't be added from this address."]);
    }

    #[Test]
    public function logInWithPasskeyFromMismatchedAddress(): void
    {
        $this->mock(VerifyPasskey::class)->shouldNotReceive('__invoke');

        $this
            ->postJson('api/me/passkey-login', [
                'login_token' => $this->getJson('api/me/passkey-login-options')->json('login_token'),
                'credential' => self::assertionCredential('http://localhost:8001'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['credential' => "Passkeys can't be used from this address."]);
    }

    #[Test]
    public function confirmIdentityWithPasskeyFromMismatchedAddress(): void
    {
        $user = create_user();
        $this->mock(VerifyPasskey::class)->shouldNotReceive('__invoke');

        $this->getAs('api/me/passkeys/confirmation-options', $user)->assertOk();

        $this
            ->postAs(
                'api/me/passkeys/registration-options',
                ['credential' => self::assertionCredential('http://localhost:8001')],
                $user,
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['credential' => "Passkeys can't be used from this address."]);
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
    private static function assertionCredential(string $origin = 'http://localhost'): array
    {
        return [
            'id' => self::base64Url('credential'),
            'rawId' => self::base64Url('credential'),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => self::clientDataJson('webauthn.get', $origin),
                'authenticatorData' => self::base64Url(self::authenticatorData()),
                'signature' => self::base64Url('signature'),
                'userHandle' => null,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function attestationCredential(string $origin = 'http://localhost'): array
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
                'clientDataJSON' => self::clientDataJson('webauthn.create', $origin),
                'attestationObject' => self::base64Url($attestationObject),
                'transports' => [],
            ],
        ];
    }

    private static function clientDataJson(string $type, string $origin = 'http://localhost'): string
    {
        return self::base64Url(json_encode([
            'type' => $type,
            'challenge' => self::base64Url('challenge'),
            'origin' => $origin,
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

    /** @return array{User, array<string>} */
    private static function createUserWithTwoFactorEnabled(): array
    {
        $user = create_user();

        $twoFactorAuth = app(TwoFactorAuthenticator::class);
        $twoFactorAuth->enroll($user);
        $recoveryCodes = $twoFactorAuth->generateRecoveryCodes();
        $twoFactorAuth->confirm($user, $recoveryCodes);

        return [$user->refresh(), $recoveryCodes];
    }
}
