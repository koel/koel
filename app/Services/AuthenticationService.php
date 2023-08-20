<?php

namespace App\Services;

use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Values\CompositionToken;
use Illuminate\Hashing\HashManager;

class AuthenticationService
{
    public function __construct(
        private UserRepository $userRepository,
        private TokenManager $tokenManager,
        private HashManager $hash
    ) {
    }

    public function login(string $email, string $password): CompositionToken
    {
        /** @var User|null $user */
        $user = $this->userRepository->getFirstWhere('email', $email);

        if (!$user || !$this->hash->check($password, $user->password)) {
            throw new InvalidCredentialsException();
        }

        return $this->tokenManager->createCompositionToken($user);
    }

    public function logoutViaBearerToken(string $token): void
    {
        $this->tokenManager->deleteCompositionToken($token);
    }
}
