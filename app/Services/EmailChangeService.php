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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use SensitiveParameter;

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
        $expiresAt = now()->addHours(self::LINK_LIFETIME_HOURS);
        $latestRequestToken = Str::random(32);

        Cache::put(self::latestRequestCacheKey($user), $latestRequestToken, $expiresAt);

        $path = URL::temporarySignedRoute(
            'email-change.confirm',
            $expiresAt,
            [
                'user' => $user,
                'email' => $newEmail,
                'current' => self::fingerprint($user->email),
                'token' => $latestRequestToken,
            ],
            absolute: false,
        );

        $signedApiPath = Str::after($path, '/api/');
        $confirmationUrl = apply_filters(
            Filter::EMAIL_CHANGE_URL,
            client_url('email-change/' . base64_encode($signedApiPath)),
            $user,
        );

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

    public function confirmChange(
        User $user,
        string $newEmail,
        string $currentEmailFingerprint,
        #[SensitiveParameter]
        string $requestToken,
    ): EmailChangeResult {
        if ($user->sso_provider) {
            return EmailChangeResult::SINGLE_SIGN_ON;
        }

        $isLatestRequest = hash_equals((string) Cache::get(self::latestRequestCacheKey($user)), $requestToken);

        if (!$isLatestRequest || !hash_equals(self::fingerprint($user->email), $currentEmailFingerprint)) {
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

        Cache::forget(self::latestRequestCacheKey($user));

        return EmailChangeResult::CHANGED;
    }

    private static function latestRequestCacheKey(User $user): string
    {
        return cache_key('latest email change request', $user->id);
    }

    private static function fingerprint(string $email): string
    {
        return hash('sha256', strtolower($email));
    }
}
