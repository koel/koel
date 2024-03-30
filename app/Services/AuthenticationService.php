<?php

namespace App\Services;

use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Values\CompositeToken;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Hashing\HashManager;
use Illuminate\Support\Facades\Password;

class AuthenticationService
{
    public function __construct(
        private UserRepository $userRepository,
        private TokenManager $tokenManager,
        private HashManager $hash,
        private PasswordBroker $passwordBroker
    ) {
    }

    public function login(string $email, string $password): CompositeToken
    {
        /** @var User|null $user */
        $user = $this->userRepository->getFirstWhere('email', $email);

        if (!$user || !$this->hash->check($password, $user->password)) {
            throw new InvalidCredentialsException();
        }

        if ($this->hash->needsRehash($user->password)) {
            $user->password = $this->hash->make($password);
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

    public function generatePasswordResetToken(User $user): string
    {
        return $this->passwordBroker->createToken($user);
    }

    public function tryResetPasswordUsingBroker(string $email, string $password, string $token): bool
    {
        $credentials = [
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
            'token' => $token,
        ];

        $status = $this->passwordBroker->reset($credentials, function (User $user, string $password): void {
            $user->password = $this->hash->make($password);
            $user->save();
            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET;
    }
}
