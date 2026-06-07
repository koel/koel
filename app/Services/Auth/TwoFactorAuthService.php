<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\Auth\Support\NullQrCodeProvider;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RobThree\Auth\TwoFactorAuth;
use SensitiveParameter;

class TwoFactorAuthService
{
    private const RECOVERY_CODE_COUNT = 8;

    private TwoFactorAuth $totp;

    public function __construct(#[Config('app.name', default: 'Koel')] string $issuer)
    {
        $this->totp = new TwoFactorAuth(new NullQrCodeProvider(), $issuer);
    }

    public function generateSecret(): string
    {
        return $this->totp->createSecret();
    }

    public function provisioningUri(User $user, #[SensitiveParameter] string $secret): string
    {
        return $this->totp->getQRText($user->email, $secret);
    }

    public function verifyCode(#[SensitiveParameter] string $secret, #[SensitiveParameter] string $code): bool
    {
        return $this->totp->verifyCode($secret, $code);
    }

    /** @return array<int, string> Cleartext codes — caller must show once and store hashes. */
    public function generateRecoveryCodes(): array
    {
        return array_map(
            static fn (): string => sprintf('%s-%s', Str::random(5), Str::random(5)),
            range(1, self::RECOVERY_CODE_COUNT),
        );
    }

    /** @param array<int, string> $cleartextCodes */
    public function hashRecoveryCodes(#[SensitiveParameter] array $cleartextCodes): array
    {
        return array_map(Hash::make(...), $cleartextCodes);
    }

    /**
     * Find and remove a matching recovery code from the user's stored hashes.
     *
     * @return bool true if a code matched and was consumed, false otherwise.
     */
    public function consumeRecoveryCode(User $user, #[SensitiveParameter] string $cleartextCode): bool
    {
        $hashes = $user->two_factor_recovery_codes ?? [];

        foreach ($hashes as $index => $hash) {
            if (!Hash::check($cleartextCode, $hash)) {
                continue;
            }

            unset($hashes[$index]);
            $user->two_factor_recovery_codes = array_values($hashes);
            $user->save();

            return true;
        }

        return false;
    }
}
