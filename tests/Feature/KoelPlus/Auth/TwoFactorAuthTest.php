<?php

namespace Tests\Feature\KoelPlus\Auth;

use App\Models\User;
use App\Services\Auth\TwoFactorAuthService;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use RobThree\Auth\TwoFactorAuth;
use Tests\PlusTestCase;

use function Tests\create_user;

class TwoFactorAuthTest extends PlusTestCase
{
    private TwoFactorAuth $totp;

    public function setUp(): void
    {
        parent::setUp();

        // Re-use the same RobThree instance the service uses, so we can compute valid codes in tests.
        $reflection = new \ReflectionClass(app(TwoFactorAuthService::class));
        $property = $reflection->getProperty('totp');
        $this->totp = $property->getValue(app(TwoFactorAuthService::class));
    }

    #[Test]
    public function setupReturnsProvisioningUriAndUnconfirmedSecret(): void
    {
        $user = create_user();

        $response = $this->postAs('api/me/two-factor', [], $user)->assertOk()->json();

        self::assertStringStartsWith('otpauth://totp/', $response['provisioning_uri']);

        $user->refresh();
        self::assertNotNull($user->two_factor_secret);
        self::assertNull($user->two_factor_confirmed_at);
        self::assertNull($user->two_factor_recovery_codes);
    }

    #[Test]
    public function confirmWithValidCodeSetsConfirmedAtAndReturnsRecoveryCodes(): void
    {
        $user = create_user();
        $this->postAs('api/me/two-factor', [], $user);
        $user->refresh();

        $code = $this->totp->getCode($user->two_factor_secret);

        $response = $this->postAs('api/me/two-factor/confirm', ['code' => $code], $user)->assertOk()->json();

        self::assertCount(8, $response['recovery_codes']);

        $user->refresh();
        self::assertNotNull($user->two_factor_confirmed_at);
        self::assertCount(8, $user->two_factor_recovery_codes);
        // Recovery codes must be hashed in DB, not stored as cleartext.
        self::assertTrue(Hash::check($response['recovery_codes'][0], $user->two_factor_recovery_codes[0]));
    }

    #[Test]
    public function confirmWithInvalidCodeReturns422(): void
    {
        $user = create_user();
        $this->postAs('api/me/two-factor', [], $user);

        $this->postAs('api/me/two-factor/confirm', ['code' => '000000'], $user)->assertUnprocessable();

        $user->refresh();
        self::assertNull($user->two_factor_confirmed_at);
    }

    #[Test]
    public function loginIntoTwoFactorAccountReturnsRequiresTwoFactor(): void
    {
        $user = $this->createUserWithTwoFactorEnabled();

        $response = $this
            ->post('api/me', [
                'email' => $user->email,
                'password' => 'secret',
            ])
            ->assertOk()
            ->json();

        self::assertTrue($response['two_factor']);
        self::assertNotEmpty($response['login_token']);
    }

    #[Test]
    public function challengeWithValidCodeMintsToken(): void
    {
        $user = $this->createUserWithTwoFactorEnabled();
        $loginToken = $this->post('api/me', ['email' => $user->email, 'password' => 'secret'])->json('login_token');

        $code = $this->totp->getCode($user->two_factor_secret);

        $response = $this
            ->post('api/me/two-factor-challenge', [
                'login_token' => $loginToken,
                'code' => $code,
            ])
            ->assertOk()
            ->json();

        self::assertNotEmpty($response['token']);
        self::assertNotEmpty($response['audio-token']);
    }

    #[Test]
    public function challengeWithRecoveryCodeMintsTokenAndConsumesIt(): void
    {
        $user = $this->createUserWithTwoFactorEnabled();
        $loginToken = $this->post('api/me', ['email' => $user->email, 'password' => 'secret'])->json('login_token');

        $recoveryCode = $this->createUserWithTwoFactorEnabledRecoveryCode;

        $this->post('api/me/two-factor-challenge', [
            'login_token' => $loginToken,
            'code' => $recoveryCode,
        ])->assertOk();

        // Same recovery code can't be reused.
        $secondLoginToken = $this->post('api/me', ['email' => $user->email, 'password' => 'secret'])->json(
            'login_token',
        );
        $this->post('api/me/two-factor-challenge', [
            'login_token' => $secondLoginToken,
            'code' => $recoveryCode,
        ])->assertUnauthorized();
    }

    #[Test]
    public function challengeWithInvalidCodeFailsWithoutConsumingLoginToken(): void
    {
        $user = $this->createUserWithTwoFactorEnabled();
        $loginToken = $this->post('api/me', ['email' => $user->email, 'password' => 'secret'])->json('login_token');

        $this->post('api/me/two-factor-challenge', [
            'login_token' => $loginToken,
            'code' => '000000',
        ])->assertUnauthorized();

        // The login token survives an invalid attempt — user can retry.
        $code = $this->totp->getCode($user->two_factor_secret);
        $this->post('api/me/two-factor-challenge', [
            'login_token' => $loginToken,
            'code' => $code,
        ])->assertOk();
    }

    #[Test]
    public function disableTwoFactorWithValidCodeClearsAllFields(): void
    {
        $user = $this->createUserWithTwoFactorEnabled();
        $code = $this->totp->getCode($user->two_factor_secret);

        $this->deleteAs('api/me/two-factor', ['code' => $code], $user)->assertNoContent();

        $user->refresh();
        self::assertNull($user->two_factor_secret);
        self::assertNull($user->two_factor_recovery_codes);
        self::assertNull($user->two_factor_confirmed_at);
    }

    #[Test]
    public function regenerateRecoveryCodesReplacesOldOnes(): void
    {
        $user = $this->createUserWithTwoFactorEnabled();
        $oldCodes = $user->two_factor_recovery_codes;
        $code = $this->totp->getCode($user->two_factor_secret);

        $response = $this->postAs('api/me/two-factor/recovery-codes', ['code' => $code], $user)->assertOk()->json();

        self::assertCount(8, $response['recovery_codes']);
        $user->refresh();
        self::assertNotSame($oldCodes, $user->two_factor_recovery_codes);
    }

    private ?string $createUserWithTwoFactorEnabledRecoveryCode = null;

    private function createUserWithTwoFactorEnabled(): User
    {
        $user = create_user(['password' => Hash::make('secret')]);

        $service = app(TwoFactorAuthService::class);
        $secret = $service->generateSecret();
        $cleartextCodes = $service->generateRecoveryCodes();

        $user->two_factor_secret = $secret;
        $user->two_factor_recovery_codes = $service->hashRecoveryCodes($cleartextCodes);
        $user->two_factor_confirmed_at = now();
        $user->save();

        $this->createUserWithTwoFactorEnabledRecoveryCode = $cleartextCodes[0];

        return $user->refresh();
    }
}
