<?php

namespace App\Services;

use App\Mail\PasswordChanged;
use App\Mail\TwoFactorDisabled;
use App\Mail\TwoFactorEnabled;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SecurityNoticeService
{
    public function notifyPasswordChanged(User $user): void
    {
        self::sendNotice($user, new PasswordChanged($user));
    }

    public function notifyTwoFactorEnabled(User $user): void
    {
        self::sendNotice($user, new TwoFactorEnabled($user));
    }

    public function notifyTwoFactorDisabled(User $user): void
    {
        self::sendNotice($user, new TwoFactorDisabled($user));
    }

    private static function sendNotice(User $user, Mailable $notice): void
    {
        if (!mailer_configured()) {
            return;
        }

        try {
            Mail::to($user->email)->queue($notice);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
