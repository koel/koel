<?php

namespace App\Services\Auth;

use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\InvalidLoginTokenException;
use App\Exceptions\RequiresTwoFactorException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Values\CompositeToken;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use SensitiveParameter;
use Throwable;

class AuthenticationService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TokenManager $tokenManager,
        private readonly PasswordBroker $passwordBroker,
        private readonly TwoFactorAuthenticator $twoFactorAuth,
    ) {}

    public function authenticate(string $email, #[SensitiveParameter] string $password): User
    {
        $user = $this->userRepository->findFirstWhere('email', $email);

        if (!$user || !Hash::check($password, $user->password)) {
            throw new InvalidCredentialsException();
        }

        if (Hash::needsRehash($user->password)) {
            $user->password = Hash::make($password);
            $user->save();
        }

        return $user;
    }

    public function login(string $email, #[SensitiveParameter] string $password): CompositeToken
    {
        $user = $this->authenticate($email, $password);

        if ($user->hasTwoFactorEnabled()) {
            throw new RequiresTwoFactorException();
        }

        return $this->logUserIn($user);
    }

    public function generateTwoFactorLoginToken(User $user): string
    {
        $token = Str::random(32);
        Cache::set(cache_key('two-factor login token', $token), encrypt($user->id), 60 * 5);

        return $token;
    }

    public function loginViaTwoFactorChallenge(
        #[SensitiveParameter]
        string $loginToken,
        #[SensitiveParameter]
        string $code,
    ): CompositeToken {
        $cacheKey = cache_key('two-factor login token', $loginToken);

        $user = $this->userRepository->getOne(self::peekAndDecryptUserIdFromCache($cacheKey));

        if (!$this->twoFactorAuth->verify($user, $code)) {
            throw new InvalidCredentialsException();
        }

        Cache::forget($cacheKey);

        return $this->logUserIn($user);
    }

    public function logUserIn(User $user): CompositeToken
    {
        return $this->tokenManager->createCompositeToken($user);
    }

    public function logoutViaBearerToken(#[SensitiveParameter] string $token): void
    {
        $this->tokenManager->deleteCompositionToken($token);
    }

    public function trySendResetPasswordLink(string $email): bool
    {
        return $this->passwordBroker->sendResetLink(['email' => $email]) === Password::RESET_LINK_SENT;
    }

    public function tryResetPasswordUsingBroker(
        string $email,
        #[SensitiveParameter]
        string $password,
        #[SensitiveParameter]
        string $token,
    ): bool {
        $credentials = [
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
            'token' => $token,
        ];

        $status = $this->passwordBroker->reset($credentials, static function (
            User $user,
            #[SensitiveParameter]
            string $password,
        ): void {
            $user->password = Hash::make($password);
            $user->save();
            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET;
    }

    public function generateOneTimeToken(User $user): string
    {
        $token = Str::random(24);
        Cache::set(cache_key('one-time token', $token), encrypt($user->id), 60 * 10);

        return $token;
    }

    public function loginViaOneTimeToken(#[SensitiveParameter] string $token): CompositeToken
    {
        $cacheKey = cache_key('one-time token', $token);
        $userId = self::peekAndDecryptUserIdFromCache($cacheKey);
        Cache::forget($cacheKey);

        return $this->logUserIn($this->userRepository->getOne($userId));
    }

    private static function peekAndDecryptUserIdFromCache(string $cacheKey): int
    {
        $encryptedUserId = Cache::get($cacheKey);

        throw_unless($encryptedUserId, InvalidLoginTokenException::create());

        try {
            return decrypt($encryptedUserId);
        } catch (Throwable $e) {
            throw InvalidLoginTokenException::create($e);
        }
    }
}
