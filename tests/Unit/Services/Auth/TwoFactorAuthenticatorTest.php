<?php

namespace Tests\Unit\Services\Auth;

use App\Services\Auth\RecoveryCodeFactory;
use App\Services\Auth\TwoFactorAuthenticator;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use RobThree\Auth\TwoFactorAuth as Totp;
use Tests\TestCase;

use function Tests\create_user;

class TwoFactorAuthenticatorTest extends TestCase
{
    private Totp|MockInterface $totp;
    private RecoveryCodeFactory|MockInterface $recoveryCodeFactory;
    private TwoFactorAuthenticator $authenticator;

    public function setUp(): void
    {
        parent::setUp();

        $this->totp = $this->mock(Totp::class);
        $this->recoveryCodeFactory = $this->mock(RecoveryCodeFactory::class);

        $this->authenticator = new TwoFactorAuthenticator($this->recoveryCodeFactory, 'koel-test', $this->totp);
    }

    #[Test]
    public function setUpReturnsProvisioningUriAndResetsState(): void
    {
        $user = create_user();
        $user->two_factor_secret = 'OLDSECRET';
        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = ['OLD1', 'OLD2'];
        $user->save();

        $this->totp->expects('createSecret')->andReturn('NEWSECRET');
        $this->totp->expects('getQRText')->with($user->email, 'NEWSECRET')->andReturn('otpauth://totp/test');

        $uri = $this->authenticator->setUp($user);

        self::assertSame('otpauth://totp/test', $uri);

        $user->refresh();
        self::assertSame('NEWSECRET', $user->two_factor_secret);
        self::assertNull($user->two_factor_confirmed_at);
        self::assertNull($user->two_factor_recovery_codes);
    }

    #[Test]
    public function generateRecoveryCodesDelegatesToFactory(): void
    {
        $this->recoveryCodeFactory->expects('generateCodes')->with(8)->andReturn(['CODE_A', 'CODE_B']);

        self::assertSame(['CODE_A', 'CODE_B'], $this->authenticator->generateRecoveryCodes());
    }

    #[Test]
    public function regenerateRecoveryCodesPersistsAndReturnsNewCodes(): void
    {
        $user = create_user();
        $user->two_factor_recovery_codes = ['OLD1', 'OLD2'];
        $user->save();

        $this->recoveryCodeFactory->expects('generateCodes')->with(8)->andReturn(['NEW1', 'NEW2']);

        $codes = $this->authenticator->regenerateRecoveryCodes($user);

        self::assertSame(['NEW1', 'NEW2'], $codes);

        $user->refresh();
        self::assertSame(['NEW1', 'NEW2'], $user->two_factor_recovery_codes);
    }

    #[Test]
    public function confirmPersistsRecoveryCodesAndStampsConfirmedAt(): void
    {
        $user = create_user();
        $user->two_factor_secret = 'SECRET';
        $user->save();

        $this->authenticator->confirm($user, ['CODE_A', 'CODE_B']);

        $user->refresh();
        self::assertSame(['CODE_A', 'CODE_B'], $user->two_factor_recovery_codes);
        self::assertNotNull($user->two_factor_confirmed_at);
    }

    #[Test]
    public function verifyAcceptsValidTotpCode(): void
    {
        $user = create_user();
        $user->two_factor_secret = 'SECRET';
        $user->save();

        $this->totp->expects('verifyCode')->with('SECRET', '123456')->andReturnTrue();

        self::assertTrue($this->authenticator->verify($user, '123456'));
    }

    #[Test]
    public function verifyConsumesRecoveryCodeWhenTotpFails(): void
    {
        $user = create_user();
        $user->two_factor_secret = 'SECRET';
        $user->two_factor_recovery_codes = ['CODE_A', 'CODE_B', 'CODE_C'];
        $user->save();

        $this->totp->expects('verifyCode')->with('SECRET', 'CODE_B')->andReturnFalse();

        self::assertTrue($this->authenticator->verify($user, 'CODE_B'));

        $user->refresh();
        self::assertSame(['CODE_A', 'CODE_C'], $user->two_factor_recovery_codes);
    }

    #[Test]
    public function verifyRejectsCodeMatchingNeitherTotpNorRecovery(): void
    {
        $user = create_user();
        $user->two_factor_secret = 'SECRET';
        $user->two_factor_recovery_codes = ['CODE_A'];
        $user->save();

        $this->totp->expects('verifyCode')->with('SECRET', 'WRONG')->andReturnFalse();

        self::assertFalse($this->authenticator->verify($user, 'WRONG'));

        $user->refresh();
        self::assertSame(['CODE_A'], $user->two_factor_recovery_codes);
    }

    #[Test]
    public function verifySkipsTotpCheckWhenSecretIsNull(): void
    {
        $user = create_user();

        // No two_factor_secret persisted — TOTP path must short-circuit.
        // Mock is strict: an unexpected call to totp->verifyCode would fail this test.
        self::assertFalse($this->authenticator->verify($user, '123456'));
    }

    #[Test]
    public function disableClearsAllTwoFactorFields(): void
    {
        $user = create_user();
        $user->two_factor_secret = 'SECRET';
        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = ['CODE_A', 'CODE_B'];
        $user->save();

        $this->authenticator->disable($user);

        $user->refresh();
        self::assertNull($user->two_factor_secret);
        self::assertNull($user->two_factor_confirmed_at);
        self::assertNull($user->two_factor_recovery_codes);
    }
}
