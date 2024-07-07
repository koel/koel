<?php

namespace App\Services;

use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Values\CompositeToken;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthenticationService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TokenManager $tokenManager,
        private readonly PasswordBroker $passwordBroker
    ) {
    }

    public function login(string $email, string $password): CompositeToken
    {
        $user = $this->userRepository->findFirstWhere('email', $email);

        if (!$user || !Hash::check($password, $user->password)) {
            throw new InvalidCredentialsException();
        }

        if (Hash::needsRehash($user->password)) {
            $user->password = Hash::make($password);
            $user->save();
        }

        return $this->logUserIn($user);
    }

    public function logUserIn(User $user): CompositeToken
    {
        return $this->tokenManager->createCompositeToken($user);
    }

    public function logoutViaBearerToken(string $token): void
    {
        $this->tokenManager->deleteCompositionToken($token);
    }

    public function trySendResetPasswordLink(string $email): bool
    {
        return $this->passwordBroker->sendResetLink(['email' => $email]) === Password::RESET_LINK_SENT;
    }

    public function tryResetPasswordUsingBroker(string $email, string $password, string $token): bool
    {
        $credentials = [
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
            'token' => $token,
        ];

        $status = $this->passwordBroker->reset($credentials, static function (User $user, string $password): void {
            $user->password = Hash::make($password);
            $user->save();
            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET;
    }

    public function generateOneTimeToken(User $user): string
    {
        $token = bin2hex(random_bytes(12));
        Cache::set("one-time-token.$token", encrypt($user->id), 60 * 10);

        return $token;
    }

    public function loginViaOneTimeToken(string $token): CompositeToken
    {
        return $this->logUserIn($this->userRepository->getOne(decrypt(Cache::get("one-time-token.$token"))));
    }
}
