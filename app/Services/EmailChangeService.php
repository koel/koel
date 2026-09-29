<?php

namespace App\Services;

use App\Enums\EmailChangeResult;
use App\Hooks\Filter;
use App\Mail\ConfirmEmailChange;
use App\Mail\EmailChanged;
use App\Mail\EmailChangeRequested;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class EmailChangeService
{
    private const int LINK_LIFETIME_HOURS = 24;

    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function requiresConfirmation(User $user, string $newEmail): bool
    {
        return $newEmail !== $user->email && !$user->sso_provider && mailer_configured();
    }

    public function requestChange(User $user, string $newEmail): void
    {
        $path = URL::temporarySignedRoute(
            'email-change.confirm',
            now()->addHours(self::LINK_LIFETIME_HOURS),
            ['user' => $user, 'email' => $newEmail, 'current' => self::fingerprint($user->email)],
            absolute: false,
        );

        $confirmationUrl = apply_filters(Filter::EMAIL_CHANGE_URL, url($path), $user);

        Mail::to($newEmail)->queue(new ConfirmEmailChange($user, $newEmail, $confirmationUrl));
        Mail::to($user->email)->queue(new EmailChangeRequested($user, $newEmail));
    }

    public function notifyChange(User $user, string $previousEmail): void
    {
        if (!mailer_configured()) {
            return;
        }

        Mail::to($previousEmail)->queue(new EmailChanged($user, $previousEmail, $user->email));
        Mail::to($user->email)->queue(new EmailChanged($user, $previousEmail, $user->email));
    }

    public function confirmChange(User $user, string $newEmail, string $currentEmailFingerprint): EmailChangeResult
    {
        if ($user->sso_provider) {
            return EmailChangeResult::SINGLE_SIGN_ON;
        }

        if (!hash_equals(self::fingerprint($user->email), $currentEmailFingerprint)) {
            return EmailChangeResult::OUTDATED;
        }

        if ($this->userRepository->findOneByEmail($newEmail)) {
            return EmailChangeResult::TAKEN;
        }

        try {
            $user->getConnection()->transaction(static fn (): bool => $user->update(['email' => $newEmail]));
        } catch (UniqueConstraintViolationException) {
            return EmailChangeResult::TAKEN;
        }

        return EmailChangeResult::CHANGED;
    }

    private static function fingerprint(string $email): string
    {
        return hash('sha256', strtolower($email));
    }
}
