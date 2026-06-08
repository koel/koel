<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Auth\Support\NullQrCodeProvider;
use Illuminate\Container\Attributes\Config;
use RobThree\Auth\TwoFactorAuth as Totp;
use SensitiveParameter;

class TwoFactorAuthenticator
{
    private const int RECOVERY_CODE_COUNT = 8;

    private Totp $totp;

    public function __construct(
        private readonly RecoveryCodeFactory $recoveryCodeFactory,
        #[Config('app.name')]
        string $issuer,
        ?Totp $totp = null,
    ) {
        $this->totp = $totp ?? new Totp(new NullQrCodeProvider(), $issuer);
    }

    public function enroll(User $user): string
    {
        $secret = $this->totp->createSecret();

        $user->two_factor_secret = $secret;
        $user->two_factor_confirmed_at = null;
        $user->two_factor_recovery_codes = null;
        $user->save();

        return $this->totp->getQRText($user->email, $secret);
    }

    /** @return list<string> */
    public function generateRecoveryCodes(): array
    {
        return $this->recoveryCodeFactory->generateCodes(self::RECOVERY_CODE_COUNT);
    }

    /** @return list<string> */
    public function regenerateRecoveryCodes(User $user): array
    {
        return tap($this->generateRecoveryCodes(), static function (array $codes) use ($user) {
            $user->two_factor_recovery_codes = $codes;
            $user->save();
        });
    }

    /** @param list<string> $recoveryCodes */
    public function confirm(User $user, array $recoveryCodes): void
    {
        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->two_factor_confirmed_at = now();
        $user->save();
    }

    public function verify(User $user, #[SensitiveParameter] string $code): bool
    {
        $secret = $user->two_factor_secret;

        return $secret && $this->totp->verifyCode($secret, $code) || self::tryConsumeRecoveryCode($user, $code);
    }

    /**
     * Find and remove a matching recovery code from the user's stored cleartext codes.
     *
     * @return bool true if a code matched and was consumed, false otherwise.
     */
    private static function tryConsumeRecoveryCode(User $user, #[SensitiveParameter] string $code): bool
    {
        $recoveryCodes = $user->two_factor_recovery_codes ?? [];
        $index = array_search($code, $recoveryCodes, true);

        if ($index === false) {
            return false;
        }

        unset($recoveryCodes[$index]);
        $user->two_factor_recovery_codes = array_values($recoveryCodes);
        $user->save();

        return true;
    }

    public function disable(User $user): void
    {
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;

        $user->save();
    }
}
