<?php

namespace App\Services\Auth\Support;

use LogicException;
use RobThree\Auth\Providers\Qr\IQRCodeProvider;

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
