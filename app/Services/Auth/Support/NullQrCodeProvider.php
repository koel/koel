<?php

namespace App\Services\Auth\Support;

use LogicException;
use RobThree\Auth\Providers\Qr\IQRCodeProvider;

/**
 * Required by RobThree\Auth\TwoFactorAuth's constructor but never invoked —
 * Koel renders QR codes on the frontend (via @vueuse/integrations/useQRCode)
 * from the otpauth:// URI exposed by TwoFactorAuthService::provisioningUri.
 */
class NullQrCodeProvider implements IQRCodeProvider
{
    public function getQRCodeImage(string $qrText, int $size): string
    {
        throw new LogicException('Koel renders QR codes on the frontend; backend should not call getQRCodeImage.');
    }

    public function getMimeType(): string
    {
        throw new LogicException('Koel renders QR codes on the frontend; backend should not call getMimeType.');
    }
}
