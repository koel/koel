<?php

namespace Tests\Feature\KoelPlus\Auth;

use App\Models\User;
use App\Services\Auth\Support\NullQrCodeProvider;
use App\Services\Auth\TwoFactorAuthenticator;
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

        $this->totp = new TwoFactorAuth(new NullQrCodeProvider(), 'koel-test');
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
    public function setupIsDeniedWhenTwoFactorIsAlreadyEnabled(): void
    {
        $user = self::createUserWithTwoFactorEnabled();
        $previousSecret = $user->two_factor_secret;
        $previousConfirmedAt = $user->two_factor_confirmed_at;
        $previousCodes = $user->two_factor_recovery_codes;

        $this->postAs('api/me/two-factor', [], $user)->assertUnprocessable();

        $user->refresh();
        self::assertSame($previousSecret, $user->two_factor_secret);
        self::assertEquals($previousConfirmedAt, $user->two_factor_confirmed_at);
        self::assertSame($previousCodes, $user->two_factor_recovery_codes);
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
        self::assertSame($response['recovery_codes'], $user->two_factor_recovery_codes);
    }

    #[Test]
    public function confirmWithInvalidCodeReturnUnprocessable(): void
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
        $user = self::createUserWithTwoFactorEnabled();

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
        $user = self::createUserWithTwoFactorEnabled();
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
        $user = self::createUserWithTwoFactorEnabled();
        $loginToken = $this->post('api/me', ['email' => $user->email, 'password' => 'secret'])->json('login_token');

        $recoveryCode = $user->two_factor_recovery_codes[0];

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
        $user = self::createUserWithTwoFactorEnabled();
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
        $user = self::createUserWithTwoFactorEnabled();
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
        $user = self::createUserWithTwoFactorEnabled();
        $oldCodes = $user->two_factor_recovery_codes;
        $code = $this->totp->getCode($user->two_factor_secret);

        $response = $this->postAs('api/me/two-factor/recovery-codes', ['code' => $code], $user)->assertOk()->json();

        self::assertCount(8, $response['recovery_codes']);
        $user->refresh();
        self::assertNotSame($oldCodes, $user->two_factor_recovery_codes);
    }

    private static function createUserWithTwoFactorEnabled(): User
    {
        $user = create_user(['password' => Hash::make('secret')]);

        $service = app(TwoFactorAuthenticator::class);
        $service->setUp($user);
        $service->confirm($user, $service->generateRecoveryCodes());

        return $user->refresh();
    }
}
